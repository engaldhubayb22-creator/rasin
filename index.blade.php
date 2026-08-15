@extends('layouts.app')
@section('title', __('app.requirements'))
@section('page-title', __('app.requirements'))

@section('content')
@php
    $rtl = app()->getLocale() === 'ar';
    $date = fn ($d) => $d ? $d->format('Y-m-d') : '—';
@endphp

<div class="flex items-center justify-between flex-wrap gap-3 mb-5">
    <div>
        <h2 class="text-xl font-bold text-slate-800">{{ __('app.requirements') }}</h2>
        <p class="text-sm text-slate-400">{{ __('app.requirements_subtitle') }}</p>
    </div>
    <button type="button" onclick="document.getElementById('req-add').classList.toggle('hidden')"
            class="inline-flex items-center gap-2 rounded-lg bg-brand-700 hover:bg-brand-800 text-white text-sm font-semibold px-4 py-2.5">+ {{ __('app.req_new') }}</button>
</div>

{{-- مؤشرات --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-xl border border-slate-200 p-5 text-center"><div class="text-slate-400 text-xs mb-1">{{ __('app.req_completed_week') }}</div><div class="text-2xl font-extrabold text-emerald-600">{{ $kpis['completed_week'] }}</div></div>
    <div class="bg-white rounded-xl border border-slate-200 p-5 text-center"><div class="text-slate-400 text-xs mb-1">{{ __('app.req_in_progress') }}</div><div class="text-2xl font-extrabold text-sky-600">{{ $kpis['in_progress'] }}</div></div>
    <div class="bg-white rounded-xl border border-slate-200 p-5 text-center"><div class="text-slate-400 text-xs mb-1">{{ __('app.req_due_today') }}</div><div class="text-2xl font-extrabold text-amber-600">{{ $kpis['due_today'] }}</div></div>
    <div class="bg-white rounded-xl border border-slate-200 p-5 text-center"><div class="text-slate-400 text-xs mb-1">{{ __('app.req_overdue') }}</div><div class="text-2xl font-extrabold text-rose-600">{{ $kpis['overdue'] }}</div></div>
</div>

{{-- نموذج إضافة بند --}}
<div id="req-add" class="hidden bg-white rounded-xl border border-slate-200 p-5 mb-5">
    <form method="POST" action="" onsubmit="this.action='{{ url('projects') }}/'+this.project_id.value+'/requirements'" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
        @csrf
        <div class="sm:col-span-3"><label class="block text-xs text-slate-500 mb-1">{{ __('app.project') }} *</label>
            <select name="project_id" required class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2"><option value="">{{ __('app.select') }}</option>
                @foreach ($projects as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
            </select></div>
        <div class="sm:col-span-2"><label class="block text-xs text-slate-500 mb-1">{{ __('app.req_code') }}</label><input name="code" class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2"></div>
        <div class="sm:col-span-4"><label class="block text-xs text-slate-500 mb-1">{{ __('app.req_item') }} *</label><input name="title" required class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2"></div>
        <div class="sm:col-span-3"><label class="block text-xs text-slate-500 mb-1">{{ __('app.req_department') }}</label>
            <select name="department" class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2"><option value="">—</option>
                @foreach ($departments as $k => $lbl)<option value="{{ $k }}">{{ __('app.'.$lbl) }}</option>@endforeach
            </select></div>
        <div class="sm:col-span-3"><label class="block text-xs text-slate-500 mb-1">{{ __('app.req_responsible') }}</label>
            <select name="assigned_to" class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2"><option value="">{{ __('app.unassigned') }}</option>
                @foreach ($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
            </select></div>
        <div class="sm:col-span-2"><label class="block text-xs text-slate-500 mb-1">{{ __('app.req_due') }}</label><input type="date" name="due_date" class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2"></div>
        <div class="sm:col-span-3"><label class="block text-xs text-slate-500 mb-1">{{ __('app.status') }}</label>
            <select name="status" class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2">
                @foreach ($statuses as $k => $lbl)<option value="{{ $k }}" @selected($k==='in_progress')>{{ __('app.'.$lbl) }}</option>@endforeach
            </select></div>
        <div class="sm:col-span-4"><label class="block text-xs text-slate-500 mb-1">{{ __('app.notes') }}</label><input name="note" class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2"></div>
        <div class="sm:col-span-12"><button class="rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold px-6 py-2">{{ __('app.add') }}</button></div>
    </form>
</div>

{{-- فلاتر --}}
<form method="GET" class="bg-white rounded-xl border border-slate-200 p-3 mb-4 flex flex-wrap gap-2 items-center text-sm">
    <select name="project_id" onchange="this.form.submit()" class="rounded-lg border border-slate-300 px-3 py-2"><option value="">{{ __('app.all_projects') }}</option>
        @foreach ($projects as $p)<option value="{{ $p->id }}" @selected(($filters['project_id'] ?? '')==$p->id)>{{ $p->name }}</option>@endforeach</select>
    <select name="department" onchange="this.form.submit()" class="rounded-lg border border-slate-300 px-3 py-2"><option value="">{{ __('app.all_departments') }}</option>
        @foreach ($departments as $k => $lbl)<option value="{{ $k }}" @selected(($filters['department'] ?? '')==$k)>{{ __('app.'.$lbl) }}</option>@endforeach</select>
    <select name="status" onchange="this.form.submit()" class="rounded-lg border border-slate-300 px-3 py-2"><option value="">{{ __('app.all_statuses') }}</option>
        @foreach ($statuses as $k => $lbl)<option value="{{ $k }}" @selected(($filters['status'] ?? '')==$k)>{{ __('app.'.$lbl) }}</option>@endforeach</select>
    <select name="assigned_to" onchange="this.form.submit()" class="rounded-lg border border-slate-300 px-3 py-2"><option value="">{{ __('app.all_responsible') }}</option>
        @foreach ($users as $u)<option value="{{ $u->id }}" @selected(($filters['assigned_to'] ?? '')==$u->id)>{{ $u->name }}</option>@endforeach</select>
    @if (array_filter($filters))<a href="{{ route('requirements.index') }}" class="text-slate-400 hover:text-rose-600 px-2">✕ {{ __('app.clear') }}</a>@endif
</form>

{{-- جدول أكونكس مجمّع حسب المشروع (كثيف بنمط شبكة) --}}
@forelse ($grouped as $projectId => $items)
    @php $proj = $items->first()->project; $overdue = $items->filter(fn ($r) => $r->isOverdue())->count(); @endphp
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden mb-3">
        <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 flex items-center gap-3 flex-wrap">
            <span class="font-bold text-slate-700 text-[13px]">{{ __('app.project') }}: {{ $proj->name ?? '—' }}</span>
            <span class="text-xs text-slate-400">{{ __('app.req_count') }}: {{ $items->count() }}</span>
            @if ($overdue)<span class="text-xs text-rose-600 font-semibold">⚠ {{ $overdue }} {{ __('app.req_overdue') }}</span>@endif
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-[13px] {{ $rtl ? 'text-right' : 'text-left' }}">
                <thead class="bg-slate-50/70 text-slate-400 text-[11px] border-b border-slate-100">
                    <tr>
                        <th class="px-3 py-2 w-8"><input type="checkbox" class="rounded border-slate-300 align-middle" onclick="this.closest('table').querySelectorAll('.row-chk').forEach(c=>c.checked=this.checked)"></th>
                        <th class="px-3 py-2 w-24">{{ __('app.req_code') }}</th>
                        <th class="px-3 py-2">{{ __('app.req_item') }}</th>
                        <th class="px-3 py-2">{{ __('app.req_department') }}</th>
                        <th class="px-3 py-2">{{ __('app.req_responsible') }}</th>
                        <th class="px-3 py-2">{{ __('app.req_due') }}</th>
                        <th class="px-3 py-2">{{ __('app.status') }}</th>
                        <th class="px-3 py-2 w-8"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($items as $r)
                        <tr class="hover:bg-sky-50/40 transition-colors {{ $r->isOverdue() ? 'bg-rose-50/30' : '' }}">
                            <td class="px-3 py-1.5"><input type="checkbox" class="row-chk rounded border-slate-300 align-middle"></td>
                            <td class="px-3 py-1.5 text-slate-400 text-xs whitespace-nowrap">{{ $r->code ?? '—' }}</td>
                            <td class="px-3 py-1.5">
                                <div class="font-medium text-slate-800 leading-tight">{{ $r->title }}</div>
                                @if ($r->note)<div class="text-[11px] text-slate-400 leading-tight">{{ $r->note }}</div>@endif
                            </td>
                            <td class="px-3 py-1.5 text-slate-500 whitespace-nowrap">{{ $r->departmentLabel() }}</td>
                            <td class="px-3 py-1.5">
                                <form method="POST" action="{{ route('requirements.update', $r) }}">
                                    @csrf @method('PATCH')<input type="hidden" name="title" value="{{ $r->title }}">
                                    <select name="assigned_to" onchange="this.form.submit()" class="border-0 bg-transparent text-slate-600 text-[13px] focus:ring-0 cursor-pointer px-0 py-0 pe-5 -ms-1 hover:text-brand-700">
                                        <option value="">{{ __('app.unassigned') }}</option>
                                        @foreach ($users as $u)<option value="{{ $u->id }}" @selected($r->assigned_to==$u->id)>{{ $u->name }}</option>@endforeach
                                    </select>
                                </form>
                            </td>
                            <td class="px-3 py-1.5 whitespace-nowrap {{ $r->isOverdue() ? 'text-rose-600 font-semibold' : 'text-slate-500' }}">
                                <span class="inline-flex items-center gap-1"><svg class="w-3.5 h-3.5 opacity-60" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>{{ $date($r->due_date) }}</span>
                            </td>
                            <td class="px-3 py-1.5">
                                <form method="POST" action="{{ route('requirements.update', $r) }}">
                                    @csrf @method('PATCH')<input type="hidden" name="title" value="{{ $r->title }}">
                                    <select name="status" onchange="this.form.submit()" class="border-0 bg-transparent font-semibold text-{{ $r->statusColor() }}-600 text-[13px] focus:ring-0 cursor-pointer px-0 py-0 pe-5 -ms-1">
                                        @foreach ($statuses as $k => $lbl)<option value="{{ $k }}" @selected($r->status===$k)>{{ __('app.'.$lbl) }}</option>@endforeach
                                    </select>
                                </form>
                            </td>
                            <td class="px-3 py-1.5">
                                <form method="POST" action="{{ route('requirements.destroy', $r) }}" onsubmit="return confirm('{{ __('app.confirm_delete') }}')">@csrf @method('DELETE')
                                    <button class="p-1 text-slate-300 hover:text-rose-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 7h12M9 7V5h6v2m-7 0l1 12h6l1-12"/></svg></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="bg-white rounded-xl border border-dashed border-slate-300 py-12 text-center text-slate-400 text-sm">{{ __('app.no_requirements') }}</div>
@endforelse
@endsection
