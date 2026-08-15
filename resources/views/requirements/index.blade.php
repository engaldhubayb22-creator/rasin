@extends('layouts.app')
@section('title', __('app.requirements'))
@section('page-title', __('app.requirements'))

@section('content')
@php
    $rtl = app()->getLocale() === 'ar';
    $date = fn ($d) => $d ? $d->format('Y-m-d') : '—';
    // خريطة حالة → صنف اللون بنمط أكونكس
    $sClass = ['urgent' => 's-urgent', 'in_progress' => 's-in_progress', 'pending' => 's-pending', 'completed' => 's-completed'];
@endphp

{{-- ستايل جداول أكونكس (مطابق للنظام المرجعي) --}}
<style>
    .acn-wrap { direction: {{ $rtl ? 'rtl' : 'ltr' }}; font-family: Tahoma, Arial, sans-serif; }
    .acn-wrap .grid-cols-8 {}
    .acn-search-header { padding: 14px 20px; background:#fff; border:1px solid #d4d4d4; border-radius:4px; display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:10px; }
    .acn-search-title { font-size:16px; font-weight:600; color:#333; }
    .acn-search-sub { font-size:12px; color:#8a8f94; margin-top:2px; }
    .acn-btn-primary { background:#1976d2; color:#fff; border:1px solid #1565c0; border-radius:2px; padding:7px 16px; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; }
    .acn-btn-primary:hover { background:#1565c0; }
    .acn-kpis { display:flex; background:#fff; border:1px solid #d4d4d4; border-radius:4px; margin-bottom:12px; overflow:hidden; }
    .acn-kpi { padding:12px 24px; border-inline-start:1px solid #e8e8e8; flex:1; text-align:center; }
    .acn-kpi:first-child { border-inline-start:none; }
    .acn-kpi-value { font-size:22px; font-weight:700; color:#2c3e50; line-height:1; }
    .acn-kpi-label { font-size:11px; color:#7f8c8d; margin-top:5px; }
    .acn-kpi.danger .acn-kpi-value { color:#c0392b; }
    .acn-kpi.warning .acn-kpi-value { color:#d68910; }
    .acn-kpi.info .acn-kpi-value { color:#2874a6; }
    .acn-kpi.success .acn-kpi-value { color:#1e7e34; }
    .acn-toolbar { display:flex; gap:6px; padding:10px 14px; background:#f5f6f7; border:1px solid #d4d4d4; border-radius:4px; margin-bottom:12px; flex-wrap:wrap; align-items:center; }
    .acn-toolbar select { padding:5px 8px; border:1px solid #c0c4c8; border-radius:2px; font-size:12px; background:#fff; min-width:130px; font-family:inherit; }
    .acn-clear { color:#94a3b8; font-size:12px; text-decoration:none; padding:0 6px; }
    .acn-clear:hover { color:#c0392b; }
    .acn-table { background:#fff; border:1px solid #b0bec5; border-radius:4px; overflow:hidden; margin-bottom:14px; }
    .acn-thead, .acn-row { display:grid; grid-template-columns:36px 100px minmax(240px,1fr) 140px 150px 120px 120px 40px; align-items:center; }
    .acn-thead { background:#e8eef3; font-size:11px; font-weight:600; color:#2c3e50; border-bottom:2px solid #90a4ae; }
    .acn-thead > div { padding:8px 10px; border-inline-start:1px solid #cfd8dc; text-align:{{ $rtl ? 'right' : 'left' }}; }
    .acn-thead > div:first-child { border-inline-start:none; text-align:center; }
    .acn-project-bar { background:#e8eef3; padding:8px 12px; color:#2c3e50; font-size:12px; display:flex; align-items:center; gap:12px; border-bottom:1px solid #b0bec5; flex-wrap:wrap; }
    .acn-project-bar strong { color:#1a365d; font-weight:600; margin-inline-end:4px; }
    .acn-project-bar .acn-meta { color:#607d8b; font-size:11px; }
    .acn-overdue { color:#d32f2f; font-weight:600; font-size:11px; }
    .acn-row { font-size:12px; border-bottom:1px solid #eceff1; color:#2c3e50; }
    .acn-row:nth-child(2n) { background:#fafbfc; }
    .acn-row:hover { background:#fff8e1 !important; }
    .acn-row > div { padding:6px 10px; border-inline-start:1px solid #f0f3f5; overflow:hidden; text-overflow:ellipsis; text-align:{{ $rtl ? 'right' : 'left' }}; }
    .acn-row > div:first-child { border-inline-start:none; text-align:center; }
    .acn-row > div:nth-child(2), .acn-row > div:nth-child(6), .acn-row > div:last-child { text-align:center; }
    .acn-code { font-family:Consolas,"Courier New",monospace; font-size:11px; color:#546e7a; font-weight:600; }
    .acn-title-cell .t { color:#0f172a; font-weight:500; font-size:12px; line-height:1.4; }
    .acn-notes { font-size:10px; color:#78909c; line-height:1.3; margin-top:2px; }
    .acn-inline-select, .acn-inline-date { width:100%; padding:3px 6px; border:1px solid transparent; background:transparent; font-size:12px; color:#2c3e50; cursor:pointer; border-radius:2px; font-family:inherit; text-align:{{ $rtl ? 'right' : 'left' }}; }
    .acn-inline-select { appearance:none; -webkit-appearance:none; background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='8' height='8' viewBox='0 0 8 8'><path d='M0 2 L4 6 L8 2' fill='none' stroke='%2378909c' stroke-width='1.5'/></svg>"); background-repeat:no-repeat; background-position:{{ $rtl ? 'left' : 'right' }} 6px center; padding-{{ $rtl ? 'left' : 'right' }}:18px; }
    .acn-inline-date { font-family:Consolas,monospace; text-align:center; display:inline-flex; align-items:center; gap:4px; justify-content:center; }
    .acn-inline-date.overdue { color:#c62828; font-weight:600; }
    .acn-inline-select:hover { border-color:#b0bec5; background-color:#fff; }
    .acn-inline-select:focus { outline:none; border-color:#1976d2; background-color:#fff; box-shadow:0 0 0 2px rgba(25,118,210,.15); }
    .acn-status-select.s-completed { color:#2e7d32; font-weight:600; }
    .acn-status-select.s-in_progress { color:#1976d2; font-weight:500; }
    .acn-status-select.s-pending { color:#757575; font-weight:500; }
    .acn-status-select.s-urgent { color:#e65100; font-weight:700; }
    .acn-del { color:#78909c; background:none; border:none; cursor:pointer; padding:2px; }
    .acn-del:hover { color:#c62828; }
    .acn-empty { padding:60px 20px; text-align:center; color:#78909c; background:#fff; border:1px solid #d4d4d4; border-radius:4px; }
    @media (max-width:900px){
        .acn-thead, .acn-row { grid-template-columns:36px 80px 1fr 110px 90px 40px; }
        .acn-thead > :nth-child(4), .acn-thead > :nth-child(5), .acn-row > :nth-child(4), .acn-row > :nth-child(5) { display:none; }
    }
</style>

<div class="acn-wrap">
    {{-- الترويسة --}}
    <div class="acn-search-header">
        <div>
            <div class="acn-search-title">{{ __('app.requirements') }}</div>
            <div class="acn-search-sub">{{ __('app.requirements_subtitle') }}</div>
        </div>
        <button type="button" onclick="document.getElementById('req-add').classList.toggle('hidden')" class="acn-btn-primary">+ {{ __('app.req_new') }}</button>
    </div>

    {{-- المؤشرات --}}
    <div class="acn-kpis">
        <div class="acn-kpi success"><div class="acn-kpi-value">{{ $kpis['completed_week'] }}</div><div class="acn-kpi-label">{{ __('app.req_completed_week') }}</div></div>
        <div class="acn-kpi info"><div class="acn-kpi-value">{{ $kpis['in_progress'] }}</div><div class="acn-kpi-label">{{ __('app.req_in_progress') }}</div></div>
        <div class="acn-kpi warning"><div class="acn-kpi-value">{{ $kpis['due_today'] }}</div><div class="acn-kpi-label">{{ __('app.req_due_today') }}</div></div>
        <div class="acn-kpi danger"><div class="acn-kpi-value">{{ $kpis['overdue'] }}</div><div class="acn-kpi-label">{{ __('app.req_overdue') }}</div></div>
    </div>

    {{-- الفلاتر --}}
    <form method="GET" class="acn-toolbar">
        <select name="project_id" onchange="this.form.submit()"><option value="">{{ __('app.all_projects') }}</option>
            @foreach ($projects as $p)<option value="{{ $p->id }}" @selected(($filters['project_id'] ?? '')==$p->id)>{{ $p->name }}</option>@endforeach</select>
        <select name="department" onchange="this.form.submit()"><option value="">{{ __('app.all_departments') }}</option>
            @foreach ($departments as $k => $lbl)<option value="{{ $k }}" @selected(($filters['department'] ?? '')==$k)>{{ __('app.'.$lbl) }}</option>@endforeach</select>
        <select name="status" onchange="this.form.submit()"><option value="">{{ __('app.all_statuses') }}</option>
            @foreach ($statuses as $k => $lbl)<option value="{{ $k }}" @selected(($filters['status'] ?? '')==$k)>{{ __('app.'.$lbl) }}</option>@endforeach</select>
        <select name="assigned_to" onchange="this.form.submit()"><option value="">{{ __('app.all_responsible') }}</option>
            @foreach ($users as $u)<option value="{{ $u->id }}" @selected(($filters['assigned_to'] ?? '')==$u->id)>{{ $u->name }}</option>@endforeach</select>
        @if (array_filter($filters))<a href="{{ route('requirements.index') }}" class="acn-clear">✕ {{ __('app.clear') }}</a>@endif
    </form>

    {{-- نموذج إضافة بند --}}
    <div id="req-add" class="hidden bg-white rounded border border-slate-300 p-4 mb-3">
        <form method="POST" action="" onsubmit="this.action='{{ url('projects') }}/'+this.project_id.value+'/requirements'" class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-end text-[13px]">
            @csrf
            <div class="sm:col-span-3"><label class="block text-xs text-slate-500 mb-1">{{ __('app.project') }} *</label>
                <select name="project_id" required class="w-full rounded border border-slate-300 px-2 py-1.5"><option value="">{{ __('app.select') }}</option>
                    @foreach ($projects as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
            <div class="sm:col-span-2"><label class="block text-xs text-slate-500 mb-1">{{ __('app.req_code') }}</label><input name="code" class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
            <div class="sm:col-span-4"><label class="block text-xs text-slate-500 mb-1">{{ __('app.req_item') }} *</label><input name="title" required class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
            <div class="sm:col-span-3"><label class="block text-xs text-slate-500 mb-1">{{ __('app.req_department') }}</label>
                <select name="department" class="w-full rounded border border-slate-300 px-2 py-1.5"><option value="">—</option>
                    @foreach ($departments as $k => $lbl)<option value="{{ $k }}">{{ __('app.'.$lbl) }}</option>@endforeach</select></div>
            <div class="sm:col-span-3"><label class="block text-xs text-slate-500 mb-1">{{ __('app.req_responsible') }}</label>
                <select name="assigned_to" class="w-full rounded border border-slate-300 px-2 py-1.5"><option value="">{{ __('app.unassigned') }}</option>
                    @foreach ($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
            <div class="sm:col-span-2"><label class="block text-xs text-slate-500 mb-1">{{ __('app.req_due') }}</label><input type="date" name="due_date" class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
            <div class="sm:col-span-3"><label class="block text-xs text-slate-500 mb-1">{{ __('app.status') }}</label>
                <select name="status" class="w-full rounded border border-slate-300 px-2 py-1.5">
                    @foreach ($statuses as $k => $lbl)<option value="{{ $k }}" @selected($k==='in_progress')>{{ __('app.'.$lbl) }}</option>@endforeach</select></div>
            <div class="sm:col-span-4"><label class="block text-xs text-slate-500 mb-1">{{ __('app.notes') }}</label><input name="note" class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
            <div class="sm:col-span-12"><button class="acn-btn-primary">{{ __('app.add') }}</button></div>
        </form>
    </div>

    {{-- الجدول (Grid بنمط أكونكس) --}}
    @if ($grouped->count())
        <div class="acn-table">
            <div class="acn-thead">
                <div><input type="checkbox" onclick="document.querySelectorAll('.row-chk').forEach(c=>c.checked=this.checked)"></div>
                <div>{{ __('app.req_code') }}</div>
                <div>{{ __('app.req_item') }}</div>
                <div>{{ __('app.req_department') }}</div>
                <div>{{ __('app.req_responsible') }}</div>
                <div>{{ __('app.req_due') }}</div>
                <div>{{ __('app.status') }}</div>
                <div></div>
            </div>

            @foreach ($grouped as $projectId => $items)
                @php $proj = $items->first()->project; $overdue = $items->filter(fn ($r) => $r->isOverdue())->count(); @endphp
                <div class="acn-project-bar">
                    <span><strong>{{ __('app.project') }}:</strong> {{ $proj->name ?? '—' }}</span>
                    <span class="acn-meta">{{ __('app.req_count') }}: {{ $items->count() }}</span>
                    @if ($overdue)<span class="acn-overdue">⚠ {{ $overdue }} {{ __('app.req_overdue') }}</span>@endif
                </div>
                @foreach ($items as $r)
                    <div class="acn-row">
                        <div><input type="checkbox" class="row-chk"></div>
                        <div class="acn-code">{{ $r->code ?? '—' }}</div>
                        <div class="acn-title-cell">
                            <div class="t">{{ $r->title }}</div>
                            @if ($r->note)<div class="acn-notes">{{ $r->note }}</div>@endif
                        </div>
                        <div>
                            <form method="POST" action="{{ route('requirements.update', $r) }}">
                                @csrf @method('PATCH')<input type="hidden" name="title" value="{{ $r->title }}">
                                <select name="department" class="acn-inline-select" onchange="this.form.submit()">
                                    <option value="">—</option>
                                    @foreach ($departments as $k => $lbl)<option value="{{ $k }}" @selected($r->department===$k)>{{ __('app.'.$lbl) }}</option>@endforeach
                                </select>
                            </form>
                        </div>
                        <div>
                            <form method="POST" action="{{ route('requirements.update', $r) }}">
                                @csrf @method('PATCH')<input type="hidden" name="title" value="{{ $r->title }}">
                                <select name="assigned_to" class="acn-inline-select" onchange="this.form.submit()">
                                    <option value="">{{ __('app.unassigned') }}</option>
                                    @foreach ($users as $u)<option value="{{ $u->id }}" @selected($r->assigned_to==$u->id)>{{ $u->name }}</option>@endforeach
                                </select>
                            </form>
                        </div>
                        <div>
                            <span class="acn-inline-date {{ $r->isOverdue() ? 'overdue' : '' }}">{{ $date($r->due_date) }}</span>
                        </div>
                        <div>
                            <form method="POST" action="{{ route('requirements.update', $r) }}">
                                @csrf @method('PATCH')<input type="hidden" name="title" value="{{ $r->title }}">
                                <select name="status" class="acn-inline-select acn-status-select {{ $sClass[$r->status] ?? '' }}" onchange="this.form.submit()">
                                    @foreach ($statuses as $k => $lbl)<option value="{{ $k }}" @selected($r->status===$k)>{{ __('app.'.$lbl) }}</option>@endforeach
                                </select>
                            </form>
                        </div>
                        <div>
                            <form method="POST" action="{{ route('requirements.destroy', $r) }}" onsubmit="return confirm('{{ __('app.confirm_delete') }}')">@csrf @method('DELETE')
                                <button class="acn-del" title="{{ __('app.delete') }}"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 7h12M9 7V5h6v2m-7 0l1 12h6l1-12"/></svg></button>
                            </form>
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>
    @else
        <div class="acn-empty">{{ __('app.no_requirements') }}</div>
    @endif
</div>
@endsection
