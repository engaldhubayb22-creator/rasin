@extends('layouts.app')
@section('title', $version->name.' · Gantt')
@section('page-title', __('app.view_gantt'))

@section('content')
@php
    $rtl = app()->getLocale() === 'ar';
    // النطاق الزمني الكلي
    $dates = collect();
    foreach ($version->activities as $a) {
        if ($a->planned_start) $dates->push($a->planned_start);
        if ($a->planned_finish) $dates->push($a->planned_finish);
    }
    $min = $dates->min();
    $max = $dates->max();
    $span = ($min && $max) ? max($min->diffInDays($max), 1) : 1;
    $pos = function ($d) use ($min, $span) {
        if (!$d || !$min) return 0;
        return min(max($min->diffInDays($d) / $span * 100, 0), 100);
    };
@endphp

<div class="mb-4 flex items-center gap-2 text-sm text-slate-400 flex-wrap">
    <a href="{{ route('projects.show', $project) }}" class="hover:text-brand-600">{{ $project->name }}</a><span>›</span>
    <a href="{{ route('schedule.show', [$project, $version]) }}" class="hover:text-brand-600">{{ $version->name }}</a><span>›</span>
    <span class="text-slate-600">Gantt</span>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="px-5 py-3.5 border-b border-slate-100 font-bold text-slate-700 flex items-center justify-between">
        <span>{{ __('app.view_gantt') }} — {{ $version->name }}</span>
        <a href="{{ route('schedule.show', [$project, $version]) }}" class="text-sm text-brand-600 hover:text-brand-700 font-medium">{{ __('app.back') }} →</a>
    </div>
    <div class="p-3 text-xs text-slate-400 border-b border-slate-100">
        {{ $min?->format('d/m/Y') ?? '—' }} → {{ $max?->format('d/m/Y') ?? '—' }}
    </div>
    <div class="divide-y divide-slate-100 max-h-[70vh] overflow-y-auto">
        @foreach ($version->activities as $a)
            @php
                $left = $pos($a->planned_start);
                $right = $pos($a->planned_finish);
                $w = max($right - $left, 1.5);
                $color = $a->is_critical ? 'bg-rose-500' : ($a->isPhase() ? 'bg-slate-400' : 'bg-brand-500');
            @endphp
            <div class="flex items-center gap-3 px-4 py-2 hover:bg-slate-50">
                <div class="w-56 shrink-0 truncate text-sm {{ $a->isPhase() ? 'font-semibold text-slate-800' : 'text-slate-600' }} {{ $a->level == 2 ? ($rtl ? 'pr-4' : 'pl-4') : '' }}">
                    <span class="text-[10px] text-slate-400">{{ $a->wbs }}</span> {{ $a->displayName() }}
                </div>
                <div class="flex-1 relative h-5 bg-slate-50 rounded">
                    <div class="absolute top-0.5 h-4 rounded {{ $color }}" style="{{ $rtl ? 'right' : 'left' }}: {{ $left }}%; width: {{ $w }}%" title="{{ $a->planned_start?->format('d/m') }} → {{ $a->planned_finish?->format('d/m') }}">
                        <div class="h-full rounded bg-black/15" style="width: {{ $a->percent }}%"></div>
                    </div>
                </div>
                <div class="w-10 shrink-0 text-xs text-slate-500 text-center">{{ $a->percent }}%</div>
            </div>
        @endforeach
    </div>
    <div class="px-5 py-3 border-t border-slate-100 flex items-center gap-4 text-xs text-slate-500">
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-brand-500"></span> {{ __('app.activity') }}</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-rose-500"></span> {{ __('app.critical') }}</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-slate-400"></span> {{ __('app.phase') }}</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-black/15"></span> {{ __('app.progress') }}</span>
    </div>
</div>
@endsection
