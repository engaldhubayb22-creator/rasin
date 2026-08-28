@extends('layouts.app')
@section('title', __('app.drawings'))
@section('page-title', __('app.drawings'))

@section('content')
@include('partials.acn-styles')
@php $dwgDate = fn ($d) => $d ? $d->format('Y-m-d') : ''; @endphp
<style>
    .dwg .acn-thead, .dwg .acn-row { display:grid; grid-template-columns:120px minmax(220px,1fr) 90px 150px 110px 40px; align-items:center; }
    @media (max-width:850px){ .dwg .acn-thead,.dwg .acn-row{ grid-template-columns:1fr 90px 130px 40px } .dwg .acn-thead>:nth-child(1),.dwg .acn-thead>:nth-child(5),.dwg .acn-row>:nth-child(1),.dwg .acn-row>:nth-child(5){display:none} }
</style>

<div class="acn-wrap">
    <div class="flex items-center justify-between flex-wrap gap-3 mb-3">
        <div>
            <h2 class="text-lg font-bold text-slate-800">{{ __('app.drawings') }}</h2>
            <p class="text-xs text-slate-400">{{ __('app.drawings_sub') }}</p>
        </div>
        @if ($canEdit)
            <button type="button" onclick="document.getElementById('dwg-add').classList.toggle('hidden')" class="acn-btn-primary">+ {{ __('app.dwg_new') }}</button>
        @endif
    </div>

    {{-- المؤشرات --}}
    <div class="acn-kpis">
        <div class="acn-kpi"><div class="acn-kpi-value">{{ $kpis['total'] }}</div><div class="acn-kpi-label">{{ __('app.dwg_total') }}</div></div>
        <div class="acn-kpi success"><div class="acn-kpi-value">{{ $kpis['approved'] }}</div><div class="acn-kpi-label">{{ __('app.dwg_approved') }}</div></div>
        <div class="acn-kpi warning"><div class="acn-kpi-value">{{ $kpis['under_review'] }}</div><div class="acn-kpi-label">{{ __('app.dwg_under_review') }}</div></div>
        <div class="acn-kpi"><div class="acn-kpi-value">{{ $kpis['draft'] }}</div><div class="acn-kpi-label">{{ __('app.dwg_draft') }}</div></div>
    </div>

    {{-- الفلاتر --}}
    <form method="GET" class="acn-toolbar">
        <select name="project_id" onchange="this.form.submit()"><option value="">{{ __('app.all_projects') }}</option>
            @foreach ($projects as $p)<option value="{{ $p->id }}" @selected(($filters['project_id'] ?? '')==$p->id)>{{ $p->name }}</option>@endforeach</select>
        <select name="discipline" onchange="this.form.submit()"><option value="">{{ __('app.dwg_all_disciplines') }}</option>
            @foreach ($disciplines as $k => $lbl)<option value="{{ $k }}" @selected(($filters['discipline'] ?? '')==$k)>{{ __('app.'.$lbl) }}</option>@endforeach</select>
        <select name="status" onchange="this.form.submit()"><option value="">{{ __('app.all_statuses') }}</option>
            @foreach ($statuses as $k => $lbl)<option value="{{ $k }}" @selected(($filters['status'] ?? '')==$k)>{{ __('app.'.$lbl) }}</option>@endforeach</select>
        @if (array_filter($filters))<a href="{{ route('drawings.index') }}" class="acn-clear">✕ {{ __('app.clear') }}</a>@endif
    </form>

    {{-- نموذج مخطط جديد --}}
    @if ($canEdit)
    <div id="dwg-add" class="hidden bg-white rounded border border-slate-300 p-4 mb-3">
        <form method="POST" action="{{ route('drawings.store') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-end text-[13px]">@csrf
            <div class="sm:col-span-2"><label class="block text-xs text-slate-500 mb-1">{{ __('app.dwg_code') }} *</label><input name="code" required class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
            <div class="sm:col-span-4"><label class="block text-xs text-slate-500 mb-1">{{ __('app.dwg_title') }} *</label><input name="title" required class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
            <div class="sm:col-span-2"><label class="block text-xs text-slate-500 mb-1">{{ __('app.dwg_discipline') }} *</label>
                <select name="discipline" class="w-full rounded border border-slate-300 px-2 py-1.5">@foreach ($disciplines as $k => $lbl)<option value="{{ $k }}">{{ __('app.'.$lbl) }}</option>@endforeach</select></div>
            <div class="sm:col-span-2"><label class="block text-xs text-slate-500 mb-1">{{ __('app.dwg_revision') }}</label><input name="revision" value="R00" class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
            <div class="sm:col-span-2"><label class="block text-xs text-slate-500 mb-1">{{ __('app.status') }}</label>
                <select name="status" class="w-full rounded border border-slate-300 px-2 py-1.5">@foreach ($statuses as $k => $lbl)<option value="{{ $k }}">{{ __('app.'.$lbl) }}</option>@endforeach</select></div>
            <div class="sm:col-span-3"><label class="block text-xs text-slate-500 mb-1">{{ __('app.project') }}</label>
                <select name="project_id" class="w-full rounded border border-slate-300 px-2 py-1.5"><option value="">—</option>@foreach ($projects as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
            <div class="sm:col-span-2"><label class="block text-xs text-slate-500 mb-1">{{ __('app.dwg_date') }}</label><input type="date" name="drawing_date" class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
            <div class="sm:col-span-12"><button class="acn-btn-primary">{{ __('app.add') }}</button></div>
        </form>
    </div>
    @endif

    @if ($grouped->count())
        <div class="acn-table dwg">
            <div class="acn-thead">
                <div>{{ __('app.dwg_code') }}</div>
                <div>{{ __('app.dwg_title') }}</div>
                <div>{{ __('app.dwg_revision') }}</div>
                <div>{{ __('app.status') }}</div>
                <div>{{ __('app.dwg_date') }}</div>
                <div></div>
            </div>

            @foreach ($grouped as $disc => $items)
                @php $dLabel = __('app.'.(\App\Models\Drawing::DISCIPLINES[$disc] ?? 'disc_other')); @endphp
                <div class="acn-project-bar">
                    <span><strong>{{ $dLabel }}</strong></span>
                    <span class="acn-meta">{{ $items->count() }} {{ __('app.dwg_count') }}</span>
                    <span class="acn-meta">{{ $items->where('status','approved')->count() }} {{ __('app.dwg_approved') }}</span>
                </div>
                @foreach ($items as $it)
                    <div class="acn-row">
                        <div class="acn-code">{{ $it->code }}</div>
                        <div class="acn-title-cell"><div class="t">{{ $it->title }}</div>@if ($it->project)<div class="acn-notes">{{ $it->project->name }}</div>@endif</div>
                        <div>
                            @if ($canEdit)
                                <form method="POST" action="{{ route('drawings.update', $it) }}" class="acn-ajax">@csrf @method('PATCH')
                                    <input name="revision" value="{{ $it->revision }}" class="acn-inline-input" style="font-family:Consolas,monospace;font-weight:600" onchange="this.form.requestSubmit()"></form>
                            @else
                                <span class="acn-code">{{ $it->revision }}</span>
                            @endif
                        </div>
                        <div>
                            @if ($canEdit)
                                <form method="POST" action="{{ route('drawings.update', $it) }}" class="acn-ajax">@csrf @method('PATCH')
                                    <select name="status" class="acn-inline-select acn-status-select {{ $it->statusClass() }}" onchange="this.form.requestSubmit()">
                                        @foreach ($statuses as $k => $lbl)<option value="{{ $k }}" @selected($it->status===$k)>{{ __('app.'.$lbl) }}</option>@endforeach</select>
                                </form>
                            @else
                                <span class="acn-status-select {{ $it->statusClass() }}">{{ $it->statusLabel() }}</span>
                            @endif
                        </div>
                        <div class="acn-code">{{ $dwgDate($it->drawing_date) ?: '—' }}</div>
                        <div>
                            @if ($canEdit)
                                <form method="POST" action="{{ route('drawings.destroy', $it) }}" onsubmit="return confirm('{{ __('app.confirm_delete') }}')">@csrf @method('DELETE')
                                    <button class="acn-del" title="{{ __('app.delete') }}"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 7h12M9 7V5h6v2m-7 0l1 12h6l1-12"/></svg></button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>
    @else
        <div class="acn-empty">{{ __('app.dwg_empty') }}</div>
    @endif
</div>
@endsection
