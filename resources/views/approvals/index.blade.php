@extends('layouts.app')
@section('title', __('app.approvals'))
@section('page-title', __('app.approvals'))

@section('content')
@include('partials.acn-styles')
<style>
    .apv-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    @media (max-width:1000px){ .apv-grid{ grid-template-columns:1fr } }
    .apv-card { background:#fff; border:1px solid #b0bec5; border-radius:4px; overflow:hidden; }
    .apv-card > h4 { padding:11px 14px; border-bottom:1px solid #e8eef3; font-size:13px; font-weight:700; color:#1a365d; display:flex; align-items:center; gap:8px; background:#f5f8fc; }
    .apv-card > h4 .sp { margin-inline-start:auto; }
    .apv-chip { display:inline-block; padding:2px 9px; border-radius:11px; font-size:11px; font-weight:700; }
    .apv-chip.s-completed { background:#dcf0e2; color:#1d6b3a; } .apv-chip.s-in_progress { background:#dbe9f8; color:#1c4f88; }
    .apv-chip.s-urgent { background:#fdf0d2; color:#8a5f00; } .apv-chip.s-cancelled { background:#fbdcd8; color:#9c2216; }
    .apv-body { padding:13px 14px; }
    .apv-stat { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #f2f5f9; font-size:12.5px; color:#546e7a; }
    .apv-stat b { color:#1a365d; }
    .apv-steps { margin-top:12px; }
    .apv-step { display:flex; gap:11px; padding:9px 0; border-bottom:1px solid #f2f5f9; align-items:flex-start; }
    .apv-dot { width:24px; height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; flex-shrink:0; color:#fff; }
    .apv-step .ttl { font-weight:700; font-size:12.5px; color:#2c3e50; }
    .apv-step .meta { font-size:11px; color:#78909c; margin-top:1px; }
    .apv-acts { display:flex; gap:7px; margin-top:12px; }
    .apv-btn { border:none; border-radius:3px; padding:5px 12px; font-size:12px; font-weight:600; color:#fff; cursor:pointer; }
    .apv-btn.ok { background:#2f7d4f; } .apv-btn.rt { background:#c96a1f; } .apv-btn.rj { background:#c0392b; }
    .apv-progress { font-size:11px; font-weight:700; color:#1a365d; margin:10px 0 3px; }
</style>

<div class="acn-wrap">
    <div class="flex items-center justify-between flex-wrap gap-3 mb-3">
        <div>
            <h2 class="text-lg font-bold text-slate-800">{{ __('app.approvals') }}</h2>
            <p class="text-xs text-slate-400">{{ __('app.approvals_sub') }}</p>
        </div>
        @if ($canManage)
            <button type="button" onclick="document.getElementById('apv-add').classList.toggle('hidden')" class="acn-btn-primary">+ {{ __('app.apv_new') }}</button>
        @endif
    </div>

    {{-- المؤشرات --}}
    <div class="acn-kpis">
        <div class="acn-kpi info"><div class="acn-kpi-value">{{ $kpis['pending'] }}</div><div class="acn-kpi-label">{{ __('app.apv_in_progress') }}</div></div>
        <div class="acn-kpi warning"><div class="acn-kpi-value">{{ $kpis['returned'] }}</div><div class="acn-kpi-label">{{ __('app.apv_returned_rejected') }}</div></div>
        <div class="acn-kpi success"><div class="acn-kpi-value">{{ $kpis['completed'] }}</div><div class="acn-kpi-label">{{ __('app.apv_completed') }}</div></div>
        <div class="acn-kpi"><div class="acn-kpi-value">{{ number_format($kpis['amount_pending']) }}</div><div class="acn-kpi-label">{{ __('app.apv_amount_pending') }} ({{ __('app.sar') }})</div></div>
    </div>

    {{-- الفلاتر --}}
    <form method="GET" class="acn-toolbar">
        <select name="project_id" onchange="this.form.submit()"><option value="">{{ __('app.all_projects') }}</option>
            @foreach ($projects as $p)<option value="{{ $p->id }}" @selected(($filters['project_id'] ?? '')==$p->id)>{{ $p->name }}</option>@endforeach</select>
        <select name="type" onchange="this.form.submit()"><option value="">{{ __('app.apv_all_types') }}</option>
            @foreach ($types as $k => $lbl)<option value="{{ $k }}" @selected(($filters['type'] ?? '')==$k)>{{ __('app.'.$lbl) }}</option>@endforeach</select>
        @if (array_filter($filters))<a href="{{ route('approvals.index') }}" class="acn-clear">✕ {{ __('app.clear') }}</a>@endif
    </form>

    {{-- نموذج مستند جديد --}}
    @if ($canManage)
    <div id="apv-add" class="hidden bg-white rounded border border-slate-300 p-4 mb-3">
        <form method="POST" action="{{ route('approvals.store') }}" class="text-[13px]">@csrf
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-end">
                <div class="sm:col-span-4"><label class="block text-xs text-slate-500 mb-1">{{ __('app.apv_doc') }} *</label><input name="doc" required class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
                <div class="sm:col-span-3"><label class="block text-xs text-slate-500 mb-1">{{ __('app.apv_type') }} *</label>
                    <select name="type" class="w-full rounded border border-slate-300 px-2 py-1.5">@foreach ($types as $k => $lbl)<option value="{{ $k }}">{{ __('app.'.$lbl) }}</option>@endforeach</select></div>
                <div class="sm:col-span-3"><label class="block text-xs text-slate-500 mb-1">{{ __('app.project') }}</label>
                    <select name="project_id" class="w-full rounded border border-slate-300 px-2 py-1.5"><option value="">—</option>@foreach ($projects as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
                <div class="sm:col-span-2"><label class="block text-xs text-slate-500 mb-1">{{ __('app.apv_amount') }}</label><input name="amount" type="number" step="0.01" class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
            </div>
            <div class="text-xs text-slate-500 mt-3 mb-1">{{ __('app.apv_chain') }} *</div>
            <div id="apv-steps" class="space-y-2">
                @for ($i = 0; $i < 3; $i++)
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                    <div class="sm:col-span-6"><input name="roles[]" placeholder="{{ __('app.apv_role_label') }}" class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
                    <div class="sm:col-span-6"><input name="approvers[]" placeholder="{{ __('app.apv_approver_name') }}" class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
                </div>
                @endfor
            </div>
            <div class="mt-3"><button class="acn-btn-primary">{{ __('app.add') }}</button></div>
        </form>
    </div>
    @endif

    {{-- البطاقات --}}
    @if ($items->count())
        <div class="apv-grid">
            @foreach ($items as $a)
                @php $cur = $a->currentStep(); @endphp
                <div class="apv-card">
                    <h4>{{ $a->doc }}<span class="sp"></span><span class="apv-chip {{ $a->overallStatusClass() }}">{{ $a->overallStatusLabel() }}</span></h4>
                    <div class="apv-body">
                        <div class="apv-stat"><span>{{ __('app.apv_type') }}</span><b>{{ $a->typeLabel() }}</b></div>
                        <div class="apv-stat"><span>{{ __('app.project') }}</span><b>{{ $a->project->name ?? '—' }}</b></div>
                        <div class="apv-stat"><span>{{ __('app.apv_amount') }}</span><b>{{ $a->amount ? number_format($a->amount).' '.__('app.sar') : '—' }}</b></div>
                        <div class="apv-stat"><span>{{ __('app.apv_submitted_by') }}</span><b>{{ $a->submitted_by ?? '—' }}{{ $a->submitted_at ? ' · '.$a->submitted_at->format('Y-m-d') : '' }}</b></div>

                        <div class="apv-progress">{{ __('app.apv_chain') }} ({{ $a->approvedCount() }}/{{ $a->steps->count() }})</div>
                        <div class="apv-steps">
                            @foreach ($a->steps as $i => $s)
                                <div class="apv-step">
                                    <div class="apv-dot" style="background:{{ $s->statusColor() }}">{{ $s->statusIcon() }}</div>
                                    <div style="flex:1">
                                        <div class="ttl">{{ $i + 1 }}. {{ $s->role_label }}</div>
                                        <div class="meta">{{ $s->approver_name ?? '—' }}{{ $s->decided_at ? ' · '.$s->decided_at->format('Y-m-d') : '' }}</div>
                                    </div>
                                    <span class="apv-chip {{ ['pending'=>'s-in_progress','approved'=>'s-completed','returned'=>'s-urgent','rejected'=>'s-cancelled'][$s->status] }}">{{ $s->statusLabel() }}</span>
                                </div>
                            @endforeach
                        </div>

                        @if ($cur && $canApprove)
                            <div class="apv-acts">
                                @foreach (['approved'=>'ok','returned'=>'rt','rejected'=>'rj'] as $dec => $cls)
                                    <form method="POST" action="{{ route('approvals.act', $a) }}">@csrf
                                        <input type="hidden" name="decision" value="{{ $dec }}">
                                        <button class="apv-btn {{ $cls }}">{{ __('app.apv_'.$dec.'_btn') }}</button>
                                    </form>
                                @endforeach
                                @if ($canManage)
                                    <form method="POST" action="{{ route('approvals.destroy', $a) }}" onsubmit="return confirm('{{ __('app.confirm_delete') }}')" style="margin-inline-start:auto">@csrf @method('DELETE')
                                        <button class="acn-del" title="{{ __('app.delete') }}">✕</button>
                                    </form>
                                @endif
                            </div>
                        @elseif ($canManage)
                            <div class="apv-acts">
                                <form method="POST" action="{{ route('approvals.destroy', $a) }}" onsubmit="return confirm('{{ __('app.confirm_delete') }}')" style="margin-inline-start:auto">@csrf @method('DELETE')
                                    <button class="acn-del" title="{{ __('app.delete') }}">✕</button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="acn-empty">{{ __('app.apv_empty') }}</div>
    @endif
</div>
@endsection
