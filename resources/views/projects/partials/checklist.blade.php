@php
    $clItems = $project->checklistItems;
    $clStatuses = \App\Models\ChecklistItem::STATUSES;
    $clDate = fn ($d) => $d ? $d->format('Y-m-d') : '';
    $clTotal = $project->checklistTotal();
    $clDone = $project->checklistDone();
    $clPct = $project->checklistPercent();
    $clOverdue = $clItems->filter(fn ($i) => $i->isOverdue())->count();
    $clPending = $clItems->where('status', 'pending_approval')->count();
@endphp

@include('partials.acn-styles')
<style>
    .cl-thead, .cl-row { display:grid; grid-template-columns:32px minmax(220px,1fr) 130px 105px 105px 120px 140px 130px 40px; align-items:center; }
    @media (max-width:1100px){ .cl-thead,.cl-row{ grid-template-columns:32px 1fr 120px 120px 40px } .cl-thead>:nth-child(4),.cl-thead>:nth-child(5),.cl-thead>:nth-child(7),.cl-thead>:nth-child(8),.cl-row>:nth-child(4),.cl-row>:nth-child(5),.cl-row>:nth-child(7),.cl-row>:nth-child(8){display:none} }
</style>

<div class="acn-wrap">
    {{-- مؤشرات التشك لست --}}
    <div class="acn-kpis">
        <div class="acn-kpi info"><div class="acn-kpi-value">{{ $clPct }}%</div><div class="acn-kpi-label">{{ __('app.cl_completion_pct') }}</div></div>
        <div class="acn-kpi success"><div class="acn-kpi-value">{{ $clDone }} / {{ $clTotal }}</div><div class="acn-kpi-label">{{ __('app.cl_completion') }}</div></div>
        <div class="acn-kpi warning"><div class="acn-kpi-value">{{ $clPending }}</div><div class="acn-kpi-label">{{ __('app.cl_pending_approval') }}</div></div>
        <div class="acn-kpi danger"><div class="acn-kpi-value">{{ $clOverdue }}</div><div class="acn-kpi-label">{{ __('app.req_overdue') }}</div></div>
    </div>

    @if ($clItems->isEmpty())
        <div class="acn-empty">
            <div class="text-4xl mb-3">🗂️</div>
            <p class="text-slate-500 font-medium mb-1">{{ __('app.cl_empty_title') }}</p>
            <p class="text-slate-400 text-sm mb-4">{{ __('app.cl_empty_sub') }}</p>
            <form method="POST" action="{{ route('checklist.generate', $project) }}">@csrf
                <button class="acn-btn-primary">✨ {{ __('app.cl_generate') }}</button>
            </form>
        </div>
    @else
        <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
            <div class="flex gap-2">
                <button type="button" onclick="document.getElementById('cl-add').classList.toggle('hidden')" class="acn-btn-primary">+ {{ __('app.cl_new_item') }}</button>
                <form method="POST" action="{{ route('checklist.generate', $project) }}" onsubmit="return confirm('{{ __('app.cl_reset_confirm') }}')">@csrf<input type="hidden" name="reset" value="1">
                    <button class="acn-btn-ghost">↻ {{ __('app.cl_regenerate') }}</button>
                </form>
            </div>
            <div class="text-xs text-slate-400">{{ __('app.cl_hint') }}</div>
        </div>

        {{-- نموذج إضافة بند --}}
        <div id="cl-add" class="hidden bg-white rounded border border-slate-300 p-4 mb-3">
            <form method="POST" action="{{ route('checklist.store', $project) }}" class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-end text-[13px]">
                @csrf
                <div class="sm:col-span-3"><label class="block text-xs text-slate-500 mb-1">{{ __('app.phase') }}</label><input name="phase" list="cl-phases" class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
                <div class="sm:col-span-5"><label class="block text-xs text-slate-500 mb-1">{{ __('app.req_item') }} *</label><input name="title" required class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
                <div class="sm:col-span-2"><label class="block text-xs text-slate-500 mb-1">{{ __('app.status') }}</label>
                    <select name="status" class="w-full rounded border border-slate-300 px-2 py-1.5">@foreach ($clStatuses as $k => $lbl)<option value="{{ $k }}">{{ __('app.'.$lbl) }}</option>@endforeach</select></div>
                <div class="sm:col-span-2 flex items-center h-[34px]"><label class="flex items-center gap-1.5 text-xs text-slate-600"><input type="checkbox" name="is_mandatory" value="1" checked class="rounded border-slate-300">{{ __('app.cl_mandatory') }}</label></div>
                <div class="sm:col-span-12"><button class="acn-btn-primary">{{ __('app.add') }}</button></div>
            </form>
            <datalist id="cl-phases">@foreach ($clItems->pluck('phase')->unique() as $ph)<option value="{{ $ph }}">@endforeach</datalist>
        </div>

        {{-- الجدول مجمّع حسب المرحلة --}}
        <div class="acn-table">
            <div class="acn-thead cl-thead">
                <div><input type="checkbox" onclick="document.querySelectorAll('.cl-chk').forEach(c=>c.checked=this.checked)"></div>
                <div>{{ __('app.req_item') }}</div>
                <div>{{ __('app.req_responsible') }}</div>
                <div>{{ __('app.cl_planned') }}</div>
                <div>{{ __('app.cl_actual') }}</div>
                <div>{{ __('app.status') }}</div>
                <div>{{ __('app.cl_evidence') }}</div>
                <div>{{ __('app.cl_approver') }}</div>
                <div></div>
            </div>

            @foreach ($clItems->groupBy('phase') as $phase => $items)
                @php $pDone = $items->where('status','completed')->count(); $pOver = $items->filter(fn($i)=>$i->isOverdue())->count(); @endphp
                <div class="acn-project-bar">
                    <span><strong>{{ $phase }}</strong></span>
                    <span class="acn-meta">{{ $pDone }} / {{ $items->count() }}</span>
                    @if ($pOver)<span class="acn-overdue">⚠ {{ $pOver }} {{ __('app.req_overdue') }}</span>@endif
                </div>
                @foreach ($items as $it)
                    <div class="acn-row">
                        <div><input type="checkbox" class="cl-chk"></div>
                        <div class="acn-title-cell">
                            <div class="t">{{ $it->title }}
                                @if ($it->is_mandatory)<span class="acn-badge-req">{{ __('app.cl_mandatory') }}</span>@else<span class="acn-badge-opt">{{ __('app.cl_optional') }}</span>@endif
                            </div>
                            @if ($it->notes)<div class="acn-notes">{{ $it->notes }}</div>@endif
                        </div>
                        <div>
                            <form method="POST" action="{{ route('checklist.update', $it) }}">@csrf @method('PATCH')
                                <select name="assigned_to" class="acn-inline-select" onchange="this.form.submit()"><option value="">{{ __('app.unassigned') }}</option>
                                    @foreach ($managers as $u)<option value="{{ $u->id }}" @selected($it->assigned_to==$u->id)>{{ $u->name }}</option>@endforeach</select>
                            </form>
                        </div>
                        <div>
                            <form method="POST" action="{{ route('checklist.update', $it) }}">@csrf @method('PATCH')
                                <input type="date" name="planned_date" value="{{ $clDate($it->planned_date) }}" class="acn-inline-date {{ $it->isOverdue() ? 'overdue' : '' }}" onchange="this.form.submit()"></form>
                        </div>
                        <div>
                            <form method="POST" action="{{ route('checklist.update', $it) }}">@csrf @method('PATCH')
                                <input type="date" name="actual_date" value="{{ $clDate($it->actual_date) }}" class="acn-inline-date" onchange="this.form.submit()"></form>
                        </div>
                        <div>
                            <form method="POST" action="{{ route('checklist.update', $it) }}">@csrf @method('PATCH')
                                <select name="status" class="acn-inline-select acn-status-select {{ $it->statusClass() }}" onchange="this.form.submit()">
                                    @foreach ($clStatuses as $k => $lbl)<option value="{{ $k }}" @selected($it->status===$k)>{{ __('app.'.$lbl) }}</option>@endforeach</select>
                            </form>
                        </div>
                        <div>
                            <form method="POST" action="{{ route('checklist.update', $it) }}">@csrf @method('PATCH')
                                <input name="evidence" value="{{ $it->evidence }}" placeholder="—" class="acn-inline-input" onchange="this.form.submit()"></form>
                        </div>
                        <div>
                            <form method="POST" action="{{ route('checklist.update', $it) }}">@csrf @method('PATCH')
                                <select name="approved_by" class="acn-inline-select" onchange="this.form.submit()"><option value="">—</option>
                                    @foreach ($managers as $u)<option value="{{ $u->id }}" @selected($it->approved_by==$u->id)>{{ $u->name }}</option>@endforeach</select>
                            </form>
                        </div>
                        <div>
                            <form method="POST" action="{{ route('checklist.destroy', $it) }}" onsubmit="return confirm('{{ __('app.confirm_delete') }}')">@csrf @method('DELETE')
                                <button class="acn-del" title="{{ __('app.delete') }}"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 7h12M9 7V5h6v2m-7 0l1 12h6l1-12"/></svg></button>
                            </form>
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>
    @endif
</div>
