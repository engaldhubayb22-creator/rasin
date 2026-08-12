@extends('layouts.app')
@section('title', $version->name)
@section('page-title', __('app.schedule'))

@section('content')
@php
    $rtl = app()->getLocale() === 'ar';
    $date = fn ($d) => $d ? $d->format('d/m') : '—';
    $dateY = fn ($d) => $d ? $d->format('d/m/Y') : '—';
    $alerts = $version->alerts();
    $phases = $version->phasesList();
    $overall = $version->overallPercent();
@endphp

{{-- مسار التنقّل --}}
<div class="mb-4 flex items-center gap-2 text-sm text-slate-400 flex-wrap">
    <a href="{{ route('dashboard') }}" class="hover:text-brand-600">{{ __('app.home') }}</a><span>›</span>
    <a href="{{ route('projects.index') }}" class="hover:text-brand-600">{{ __('app.projects') }}</a><span>›</span>
    <a href="{{ route('projects.show', $project) }}" class="hover:text-brand-600">{{ $project->name }}</a><span>›</span>
    <a href="{{ route('projects.show', $project) }}#schedule" class="hover:text-brand-600">{{ __('app.schedule') }}</a><span>›</span>
    <span class="text-slate-600">{{ $version->name }}</span>
</div>

{{-- ترويسة النسخة + الإجراءات --}}
<div class="bg-white rounded-xl border border-slate-200 p-6 mb-5">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div class="flex items-start gap-3">
            <div class="w-11 h-11 rounded-xl bg-brand-50 text-brand-700 grid place-items-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    <h2 class="text-xl font-bold text-slate-800">{{ $version->name }}</h2>
                    <span class="text-xs px-2.5 py-1 rounded-full bg-{{ $version->statusColor() }}-50 text-{{ $version->statusColor() }}-700">{{ $version->statusLabel() }}</span>
                </div>
                <div class="text-sm text-slate-400 mt-1">
                    {{ $project->name }} · {{ $dateY($version->period_start) }} → {{ $dateY($version->period_finish) }}
                    · {{ __('app.uploaded_by') }} {{ $version->uploader->name ?? '—' }}
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('schedule.gantt', [$project, $version]) }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold px-4 py-2.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h10M4 12h14M4 18h7"/></svg>
                {{ __('app.view_gantt') }}
            </a>
            @if ($version->status !== 'approved')
                <form method="POST" action="{{ route('schedule.decide', [$project, $version]) }}">
                    @csrf<input type="hidden" name="decision" value="approved">
                    <button class="inline-flex items-center gap-1.5 rounded-lg bg-brand-700 hover:bg-brand-800 text-white text-sm font-semibold px-4 py-2.5">✔ {{ __('app.approve_schedule') }}</button>
                </form>
            @endif
            @if ($version->status !== 'rejected')
                <form method="POST" action="{{ route('schedule.decide', [$project, $version]) }}">
                    @csrf<input type="hidden" name="decision" value="rejected">
                    <button class="inline-flex items-center gap-1.5 rounded-lg border border-rose-300 text-rose-600 hover:bg-rose-50 text-sm font-semibold px-4 py-2.5">✕ {{ __('app.reject') }}</button>
                </form>
            @endif
        </div>
    </div>
</div>

{{-- بطاقات المؤشرات --}}
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-5">
    @php
        $kpis = [
            ['overall_progress', $overall.'%', 'brand'],
            ['total_activities', $version->totalActivities(), 'brand'],
            ['phases', $version->phasesCount(), 'slate'],
            ['critical_activities', $version->criticalCount(), 'orange'],
            ['delayed', $version->delayedCount(), 'rose'],
            ['upcoming_14', $version->upcomingCount(), 'emerald'],
        ];
    @endphp
    @foreach ($kpis as [$label, $val, $color])
        <div class="bg-white rounded-xl border border-slate-200 p-5 text-center">
            <div class="text-slate-400 text-xs mb-1">{{ __('app.'.$label) }}</div>
            <div class="text-2xl font-extrabold text-{{ $color }}-600">{{ $val }}</div>
        </div>
    @endforeach
</div>

{{-- التنبيهات --}}
@if ($alerts->count())
<div class="bg-white rounded-xl border border-rose-200 overflow-hidden mb-5">
    <div class="px-5 py-3 bg-rose-50/70 border-b border-rose-100 font-bold text-rose-700 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19H19a2 2 0 001.75-2.96l-6.92-12a2 2 0 00-3.5 0l-6.92 12A2 2 0 005.07 19z"/></svg>
        {{ __('app.alerts') }} ({{ $alerts->count() }})
    </div>
    <div class="divide-y divide-slate-100 max-h-72 overflow-y-auto">
        @foreach ($alerts as $a)
            <div class="px-5 py-2.5 text-sm text-slate-600 flex items-center gap-2">
                <span class="text-rose-500">●</span>
                {{ __('app.critical_stalled') }}: {{ $a->displayName() }}
            </div>
        @endforeach
    </div>
</div>
@endif

{{-- المراحل الرئيسية --}}
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden mb-5">
    <div class="px-5 py-3.5 border-b border-slate-100 font-bold text-slate-700 flex items-center gap-2">
        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
        {{ __('app.main_phases') }}
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm {{ $rtl ? 'text-right' : 'text-left' }}">
            <thead class="bg-slate-50 text-slate-500 text-xs">
                <tr>
                    <th class="px-4 py-3 w-10">#</th>
                    <th class="px-4 py-3">{{ __('app.phase') }}</th>
                    <th class="px-4 py-3 text-center">{{ __('app.start') }}</th>
                    <th class="px-4 py-3 text-center">{{ __('app.finish') }}</th>
                    <th class="px-4 py-3 text-center">{{ __('app.progress') }}</th>
                    <th class="px-4 py-3 text-center">{{ __('app.critical') }}</th>
                    <th class="px-4 py-3 w-40">{{ __('app.progress_bar') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($phases as $i => $p)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-slate-400">{{ $i }}</td>
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $p->displayName() }}</td>
                        <td class="px-4 py-3 text-center text-slate-500">{{ $date($p->planned_start) }}</td>
                        <td class="px-4 py-3 text-center text-slate-500">{{ $date($p->planned_finish) }}</td>
                        <td class="px-4 py-3 text-center font-semibold {{ $p->percent >= 100 ? 'text-emerald-600' : ($p->percent > 0 ? 'text-brand-700' : 'text-slate-400') }}">{{ $p->percent }}%</td>
                        <td class="px-4 py-3 text-center">@if($p->is_critical)<span class="text-amber-500" title="{{ __('app.critical') }}">⚠</span>@else<span class="text-slate-300">—</span>@endif</td>
                        <td class="px-4 py-3"><div class="h-2 rounded-full bg-slate-100 overflow-hidden"><div class="h-full {{ $p->percent >= 100 ? 'bg-emerald-500' : 'bg-brand-500' }}" style="width: {{ $p->percent }}%"></div></div></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">{{ __('app.no_phases') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- جميع الأنشطة (نمط أكونكس) --}}
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden" id="activities">
    <div class="px-5 py-3.5 border-b border-slate-100 font-bold text-slate-700 flex items-center gap-2 flex-wrap">
        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        {{ __('app.all_activities') }} ({{ $version->activities->count() }})
        <div class="ms-auto flex gap-1.5 text-xs">
            <button type="button" onclick="filterAct('all', this)" class="filter-btn px-3 py-1.5 rounded-lg bg-brand-50 text-brand-700 font-semibold">{{ __('app.all') }}</button>
            <button type="button" onclick="filterAct('critical', this)" class="filter-btn px-3 py-1.5 rounded-lg text-slate-500 hover:bg-slate-50">⚠ {{ __('app.critical') }}</button>
            <button type="button" onclick="filterAct('delayed', this)" class="filter-btn px-3 py-1.5 rounded-lg text-slate-500 hover:bg-slate-50">● {{ __('app.act_delayed') }}</button>
            <button type="button" onclick="filterAct('phase', this)" class="filter-btn px-3 py-1.5 rounded-lg text-slate-500 hover:bg-slate-50">{{ __('app.main_phases') }}</button>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm {{ $rtl ? 'text-right' : 'text-left' }}">
            <thead class="bg-slate-50 text-slate-500 text-xs sticky top-0">
                <tr>
                    <th class="px-4 py-3 w-16">WBS</th>
                    <th class="px-4 py-3">{{ __('app.activity') }}</th>
                    <th class="px-4 py-3 text-center whitespace-nowrap">{{ __('app.planned_start') }}</th>
                    <th class="px-4 py-3 text-center whitespace-nowrap">{{ __('app.planned_finish') }}</th>
                    <th class="px-4 py-3 text-center whitespace-nowrap">{{ __('app.actual_start') }}</th>
                    <th class="px-4 py-3 text-center whitespace-nowrap">{{ __('app.actual_finish') }}</th>
                    <th class="px-4 py-3 text-center">{{ __('app.progress') }}</th>
                    <th class="px-4 py-3 text-center">{{ __('app.delay') }}</th>
                    <th class="px-4 py-3">{{ __('app.status') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($version->activities as $a)
                    <tr class="act-row hover:bg-slate-50 {{ $a->isPhase() ? 'bg-slate-50/60 font-semibold' : '' }}"
                        data-critical="{{ $a->is_critical ? 1 : 0 }}" data-delayed="{{ $a->status === 'delayed' || $a->delay_days > 0 ? 1 : 0 }}" data-phase="{{ $a->isPhase() ? 1 : 0 }}">
                        <td class="px-4 py-2.5 text-slate-400 text-xs">{{ $a->wbs }}</td>
                        <td class="px-4 py-2.5">
                            <div class="flex items-center gap-2 {{ $a->level == 2 ? ($rtl ? 'pr-4' : 'pl-4') : '' }}">
                                @if ($a->isPhase())<span class="text-amber-500">🗂</span>@else<span class="w-1.5 h-1.5 rounded-full bg-slate-300 shrink-0"></span>@endif
                                <span class="{{ $a->isPhase() ? 'text-slate-800' : 'text-slate-600' }}">{{ $a->displayName() }}</span>
                                @if ($a->is_critical)<span class="text-rose-500 text-xs" title="{{ __('app.critical') }}">⚠</span>@endif
                            </div>
                        </td>
                        <td class="px-4 py-2.5 text-center text-slate-500 whitespace-nowrap">{{ $date($a->planned_start) }}</td>
                        <td class="px-4 py-2.5 text-center text-slate-500 whitespace-nowrap">{{ $date($a->planned_finish) }}</td>
                        <td class="px-4 py-2.5 text-center text-slate-500 whitespace-nowrap">{{ $a->actual_start ? $date($a->actual_start) : '—' }}</td>
                        <td class="px-4 py-2.5 text-center text-slate-500 whitespace-nowrap">{{ $a->actual_finish ? $date($a->actual_finish) : '—' }}</td>
                        <td class="px-4 py-2.5 text-center font-medium {{ $a->percent >= 100 ? 'text-emerald-600' : ($a->percent > 0 ? 'text-brand-700' : 'text-rose-600') }}">{{ $a->percent }}%</td>
                        <td class="px-4 py-2.5 text-center">@if($a->delay_days > 0)<span class="text-rose-600 font-semibold">{{ $a->delay_days }}</span>@else<span class="text-slate-300">—</span>@endif</td>
                        <td class="px-4 py-2.5"><span class="text-xs px-2 py-1 rounded-full bg-{{ $a->statusColor() }}-50 text-{{ $a->statusColor() }}-700 whitespace-nowrap">{{ $a->statusLabel() }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
    function filterAct(mode, btn) {
        document.querySelectorAll('.filter-btn').forEach(b => {
            b.classList.remove('bg-brand-50','text-brand-700','font-semibold');
            b.classList.add('text-slate-500');
        });
        btn.classList.add('bg-brand-50','text-brand-700','font-semibold');
        btn.classList.remove('text-slate-500');
        document.querySelectorAll('.act-row').forEach(r => {
            let show = true;
            if (mode === 'critical') show = r.dataset.critical === '1';
            else if (mode === 'delayed') show = r.dataset.delayed === '1';
            else if (mode === 'phase') show = r.dataset.phase === '1';
            r.classList.toggle('hidden', !show);
        });
    }
</script>
@endpush
@endsection
