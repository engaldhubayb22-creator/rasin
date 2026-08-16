@extends('layouts.app')
@section('title', __('app.checklist_center'))
@section('page-title', __('app.checklist_center'))

@section('content')
@php
    $rtl = app()->getLocale() === 'ar';
    $clDate = fn ($d) => $d ? $d->format('Y-m-d') : '';
@endphp

@include('partials.acn-styles')
<style>
    .clc .acn-thead, .clc .acn-row { display:grid; grid-template-columns:32px 150px minmax(220px,1fr) 140px 110px 120px 130px 40px; align-items:center; }
    @media (max-width:1000px){ .clc .acn-thead,.clc .acn-row{ grid-template-columns:32px 1fr 120px 120px 40px } .clc .acn-thead>:nth-child(2),.clc .acn-thead>:nth-child(5),.clc .acn-thead>:nth-child(7),.clc .acn-row>:nth-child(2),.clc .acn-row>:nth-child(5),.clc .acn-row>:nth-child(7){display:none} }
</style>

<div class="acn-wrap">
    {{-- الترويسة --}}
    <div class="flex items-center justify-between flex-wrap gap-3 mb-3">
        <div>
            <h2 class="text-lg font-bold text-slate-800">{{ __('app.checklist_center') }}</h2>
            <p class="text-xs text-slate-400">{{ __('app.checklist_center_sub') }}</p>
        </div>
    </div>

    {{-- المؤشرات --}}
    <div class="acn-kpis">
        <div class="acn-kpi info"><div class="acn-kpi-value">{{ $kpis['pct'] }}%</div><div class="acn-kpi-label">{{ __('app.cl_completion_pct') }}</div></div>
        <div class="acn-kpi success"><div class="acn-kpi-value">{{ $kpis['done'] }} / {{ $kpis['total'] }}</div><div class="acn-kpi-label">{{ __('app.cl_completion') }}</div></div>
        <div class="acn-kpi warning"><div class="acn-kpi-value">{{ $kpis['pending'] }}</div><div class="acn-kpi-label">{{ __('app.cl_pending_approval') }}</div></div>
        <div class="acn-kpi danger"><div class="acn-kpi-value">{{ $kpis['overdue'] }}</div><div class="acn-kpi-label">{{ __('app.req_overdue') }}</div></div>
    </div>

    {{-- الفلاتر --}}
    <form method="GET" class="acn-toolbar">
        <select name="project_id" onchange="this.form.requestSubmit()"><option value="">{{ __('app.all_projects') }}</option>
            @foreach ($projects as $p)<option value="{{ $p->id }}" @selected(($filters['project_id'] ?? '')==$p->id)>{{ $p->name }}</option>@endforeach</select>
        <select name="phase" onchange="this.form.requestSubmit()"><option value="">{{ __('app.all_phases') }}</option>
            @foreach ($phases as $ph)<option value="{{ $ph }}" @selected(($filters['phase'] ?? '')==$ph)>{{ $ph }}</option>@endforeach</select>
        <select name="status" onchange="this.form.requestSubmit()"><option value="">{{ __('app.all_statuses') }}</option>
            @foreach ($statuses as $k => $lbl)<option value="{{ $k }}" @selected(($filters['status'] ?? '')==$k)>{{ __('app.'.$lbl) }}</option>@endforeach</select>
        <select name="assigned_to" onchange="this.form.requestSubmit()"><option value="">{{ __('app.all_responsible') }}</option>
            @foreach ($users as $u)<option value="{{ $u->id }}" @selected(($filters['assigned_to'] ?? '')==$u->id)>{{ $u->name }}</option>@endforeach</select>
        @if (array_filter($filters))<a href="{{ route('checklist.center') }}" class="acn-clear">✕ {{ __('app.clear') }}</a>@endif
    </form>

    {{-- الجدول مجمّع حسب المشروع --}}
    @if ($grouped->count())
        <div class="acn-table clc">
            <div class="acn-thead">
                <div><input type="checkbox" onclick="document.querySelectorAll('.clc-chk').forEach(c=>c.checked=this.checked)"></div>
                <div>{{ __('app.phase') }}</div>
                <div>{{ __('app.req_item') }}</div>
                <div>{{ __('app.req_responsible') }}</div>
                <div>{{ __('app.cl_planned') }}</div>
                <div>{{ __('app.status') }}</div>
                <div>{{ __('app.cl_approver') }}</div>
                <div></div>
            </div>

            @foreach ($grouped as $projectId => $items)
                @php $proj = $items->first()->project; $pDone = $items->where('status','completed')->count(); $pOver = $items->filter(fn($i)=>$i->isOverdue())->count(); @endphp
                <div class="acn-project-bar">
                    <span><strong>{{ __('app.project') }}:</strong> {{ $proj->name ?? '—' }}</span>
                    <span class="acn-meta">{{ $pDone }} / {{ $items->count() }}</span>
                    @if ($pOver)<span class="acn-overdue">⚠ {{ $pOver }} {{ __('app.req_overdue') }}</span>@endif
                </div>
                @foreach ($items as $it)
                    <div class="acn-row">
                        <div><input type="checkbox" class="clc-chk"></div>
                        <div class="text-slate-500" style="font-size:11px">{{ $it->phase }}</div>
                        <div class="acn-title-cell">
                            <div class="t">{{ $it->title }}
                                @if ($it->is_mandatory)<span class="acn-badge-req">{{ __('app.cl_mandatory') }}</span>@else<span class="acn-badge-opt">{{ __('app.cl_optional') }}</span>@endif
                            </div>
                        </div>
                        <div>
                            <form method="POST" action="{{ route('checklist.update', $it) }}" class="acn-ajax">@csrf @method('PATCH')
                                <select name="assigned_to" class="acn-inline-select" onchange="this.form.requestSubmit()"><option value="">{{ __('app.unassigned') }}</option>
                                    @foreach ($users as $u)<option value="{{ $u->id }}" @selected($it->assigned_to==$u->id)>{{ $u->name }}</option>@endforeach</select>
                            </form>
                        </div>
                        <div><span class="acn-inline-date {{ $it->isOverdue() ? 'overdue' : '' }}" style="border:none;background:none">{{ $clDate($it->planned_date) ?: '—' }}</span></div>
                        <div>
                            <form method="POST" action="{{ route('checklist.update', $it) }}" class="acn-ajax">@csrf @method('PATCH')
                                <select name="status" class="acn-inline-select acn-status-select {{ $it->statusClass() }}" onchange="this.form.requestSubmit()">
                                    @foreach ($statuses as $k => $lbl)<option value="{{ $k }}" @selected($it->status===$k)>{{ __('app.'.$lbl) }}</option>@endforeach</select>
                            </form>
                        </div>
                        <div class="text-slate-500" style="font-size:11px">{{ $it->approver->name ?? '—' }}</div>
                        <div>
                            <a href="{{ route('projects.show', $it->project) }}#checklist" class="acn-del" title="{{ __('app.open') }}"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></a>
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>
    @else
        <div class="acn-empty">{{ __('app.no_checklist_items') }}</div>
    @endif
</div>
@endsection
