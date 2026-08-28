@extends('layouts.app')
@section('title', __('app.procurement'))
@section('page-title', __('app.procurement'))

@section('content')
@include('partials.acn-styles')
@php $pDate = fn ($d) => $d ? $d->format('Y-m-d') : ''; @endphp
<style>
    .prc .acn-thead, .prc .acn-row { display:grid; grid-template-columns:minmax(200px,1fr) 130px 90px 110px 120px 90px 110px 40px; align-items:center; }
    @media (max-width:1050px){ .prc .acn-thead,.prc .acn-row{ grid-template-columns:1fr 100px 100px 40px } .prc .acn-thead>:nth-child(2),.prc .acn-thead>:nth-child(3),.prc .acn-thead>:nth-child(4),.prc .acn-thead>:nth-child(6),.prc .acn-row>:nth-child(2),.prc .acn-row>:nth-child(3),.prc .acn-row>:nth-child(4),.prc .acn-row>:nth-child(6){display:none} }
    .prc-alert { display:inline-block; padding:2px 9px; border-radius:11px; font-size:11px; font-weight:700; }
    .prc-alert.s-completed { background:#dcf0e2; color:#1d6b3a; } .prc-alert.s-urgent { background:#fdf0d2; color:#8a5f00; } .prc-alert.s-cancelled { background:#fbdcd8; color:#9c2216; }
</style>

<div class="acn-wrap">
    <div class="flex items-center justify-between flex-wrap gap-3 mb-3">
        <div>
            <h2 class="text-lg font-bold text-slate-800">{{ __('app.procurement') }}</h2>
            <p class="text-xs text-slate-400">{{ __('app.procurement_sub') }}</p>
        </div>
        @if ($canEdit)
            <button type="button" onclick="document.getElementById('prc-add').classList.toggle('hidden')" class="acn-btn-primary">+ {{ __('app.proc_new') }}</button>
        @endif
    </div>

    {{-- المؤشرات --}}
    <div class="acn-kpis">
        <div class="acn-kpi"><div class="acn-kpi-value">{{ $kpis['total'] }}</div><div class="acn-kpi-label">{{ __('app.proc_total') }}</div></div>
        <div class="acn-kpi danger"><div class="acn-kpi-value">{{ $kpis['overdue'] }}</div><div class="acn-kpi-label">{{ __('app.proc_overdue') }}</div></div>
        <div class="acn-kpi warning"><div class="acn-kpi-value">{{ $kpis['critical'] }}</div><div class="acn-kpi-label">{{ __('app.proc_critical') }}</div></div>
        <div class="acn-kpi success"><div class="acn-kpi-value">{{ $kpis['on_plan'] }}</div><div class="acn-kpi-label">{{ __('app.proc_on_plan') }}</div></div>
    </div>

    {{-- شرح المنطق --}}
    <div class="mb-3 flex items-start gap-2 rounded bg-blue-50 border border-blue-200 text-blue-800 px-4 py-2.5 text-[12.5px] leading-relaxed">
        <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>{{ __('app.proc_logic') }}</span>
    </div>

    {{-- الفلاتر --}}
    <form method="GET" class="acn-toolbar">
        <select name="project_id" onchange="this.form.submit()"><option value="">{{ __('app.all_projects') }}</option>
            @foreach ($projects as $p)<option value="{{ $p->id }}" @selected(($filters['project_id'] ?? '')==$p->id)>{{ $p->name }}</option>@endforeach</select>
        @if (array_filter($filters))<a href="{{ route('procurement.index') }}" class="acn-clear">✕ {{ __('app.clear') }}</a>@endif
    </form>

    {{-- نموذج بند توريد جديد --}}
    @if ($canEdit)
    <div id="prc-add" class="hidden bg-white rounded border border-slate-300 p-4 mb-3">
        <form method="POST" action="{{ route('procurement.store') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-end text-[13px]">@csrf
            <div class="sm:col-span-4"><label class="block text-xs text-slate-500 mb-1">{{ __('app.proc_item') }} *</label><input name="item" required class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
            <div class="sm:col-span-2"><label class="block text-xs text-slate-500 mb-1">{{ __('app.proc_activity') }}</label><input name="activity_code" placeholder="E40" class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
            <div class="sm:col-span-3"><label class="block text-xs text-slate-500 mb-1">{{ __('app.req_responsible') }}</label><input name="responsible" class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
            <div class="sm:col-span-3"><label class="block text-xs text-slate-500 mb-1">{{ __('app.project') }}</label>
                <select name="project_id" class="w-full rounded border border-slate-300 px-2 py-1.5"><option value="">—</option>@foreach ($projects as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
            <div class="sm:col-span-3"><label class="block text-xs text-slate-500 mb-1">{{ __('app.proc_need_by') }} *</label><input type="date" name="need_by" required class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
            <div class="sm:col-span-3"><label class="block text-xs text-slate-500 mb-1">{{ __('app.proc_select_by') }} *</label><input type="date" name="select_by" required class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
            <div class="sm:col-span-12"><button class="acn-btn-primary">{{ __('app.add') }}</button></div>
        </form>
    </div>
    @endif

    @if ($items->count())
        <div class="acn-table prc">
            <div class="acn-thead">
                <div>{{ __('app.proc_item') }}</div>
                <div>{{ __('app.req_responsible') }}</div>
                <div>{{ __('app.proc_activity') }}</div>
                <div>{{ __('app.proc_need_by') }}</div>
                <div>{{ __('app.proc_select_by') }}</div>
                <div>{{ __('app.proc_left') }}</div>
                <div>{{ __('app.proc_alert') }}</div>
                <div></div>
            </div>
            @foreach ($items as $it)
                <div class="acn-row">
                    <div class="acn-title-cell"><div class="t">{{ $it->item }}</div>@if ($it->project)<div class="acn-notes">{{ $it->project->name }}</div>@endif</div>
                    <div class="text-slate-500" style="font-size:11px">{{ $it->responsible ?? '—' }}</div>
                    <div>@if ($it->activity_code)<span class="acn-code" style="background:#dbe9f8;color:#1c4f88;padding:1px 6px;border-radius:8px">{{ $it->activity_code }}</span>@else — @endif</div>
                    <div class="acn-code">{{ $pDate($it->need_by) }}</div>
                    <div class="acn-code" style="color:#c0392b;font-weight:700">{{ $pDate($it->select_by) }}</div>
                    <div class="text-center" style="font-weight:700">{{ $it->daysLeft() }}<span class="text-slate-400 text-[10px]"> {{ __('app.day') }}</span></div>
                    <div><span class="prc-alert {{ $it->alertClass() }}">{{ $it->alertLabel() }}</span></div>
                    <div>
                        @if ($canEdit)
                            <form method="POST" action="{{ route('procurement.destroy', $it) }}" onsubmit="return confirm('{{ __('app.confirm_delete') }}')">@csrf @method('DELETE')
                                <button class="acn-del" title="{{ __('app.delete') }}"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 7h12M9 7V5h6v2m-7 0l1 12h6l1-12"/></svg></button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="acn-empty">{{ __('app.proc_empty') }}</div>
    @endif
</div>
@endsection
