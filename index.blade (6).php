@extends('layouts.app')
@section('title', __('app.projects'))
@section('page-title', __('app.projects'))

@section('content')
@php $fmt = fn ($n) => number_format((float) $n); $rtl = app()->getLocale()==='ar'; @endphp

<div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-5">
    <form method="GET" action="{{ route('projects.index') }}" class="flex-1 flex gap-2">
        <div class="relative flex-1 max-w-sm">
            <svg class="w-4 h-4 absolute top-1/2 -translate-y-1/2 {{ $rtl ? 'right-3' : 'left-3' }} text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('app.search_placeholder') }}"
                   class="w-full text-sm rounded-lg border border-slate-300 focus:border-brand-500 focus:ring-brand-500 {{ $rtl ? 'pr-9 pl-3' : 'pl-9 pr-3' }} py-2.5">
        </div>
        <select name="status" onchange="this.form.submit()" class="text-sm rounded-lg border border-slate-300 focus:border-brand-500 focus:ring-brand-500 py-2.5 px-3">
            <option value="">{{ __('app.all_statuses') }}</option>
            @foreach (array_keys($statuses) as $key)
                <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ __('app.status_'.$key) }}</option>
            @endforeach
        </select>
        <button class="text-sm rounded-lg bg-slate-800 text-white px-4 py-2.5 hover:bg-slate-900">{{ __('app.search') }}</button>
    </form>
    <a href="{{ route('projects.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 hover:bg-brand-700 text-white font-semibold text-sm px-4 py-2.5 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 4v16m8-8H4"/></svg>
        {{ __('app.new_project') }}
    </a>
</div>

@if ($projects->count())
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach ($projects as $p)
            <a href="{{ route('projects.show', $p) }}" class="group bg-white rounded-xl border border-slate-200 hover:border-brand-300 hover:shadow-md transition p-5 flex flex-col">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="min-w-0">
                        <div class="font-bold text-slate-800 group-hover:text-brand-700 truncate">{{ $p->name }}</div>
                        <div class="text-xs text-slate-400 mt-0.5">{{ $p->code ?? '—' }}</div>
                    </div>
                    <span class="shrink-0 text-xs px-2.5 py-1 rounded-full bg-{{ $p->statusColor() }}-50 text-{{ $p->statusColor() }}-700">{{ $p->statusLabel() }}</span>
                </div>
                <div class="space-y-1.5 text-sm text-slate-500 flex-1">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        {{ $p->client_name ?? __('app.no_client') }}
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $p->location ?? '—' }}
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-slate-400">{{ __('app.progress') }}</span>
                        <span class="font-semibold text-slate-600">{{ $p->progress }}%</span>
                    </div>
                    <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden"><div class="h-full bg-brand-500" style="width: {{ $p->progress }}%"></div></div>
                    @if ($p->contract_value)
                        <div class="mt-3 pt-3 border-t border-slate-100 text-sm">
                            <span class="text-slate-400 text-xs">{{ __('app.contract_value') }}:</span>
                            <span class="font-bold text-slate-700">{{ $fmt($p->contract_value) }}</span>
                            <span class="text-xs text-slate-400">{{ __('app.currency') }}</span>
                        </div>
                    @endif
                </div>
            </a>
        @endforeach
    </div>
    <div class="mt-6">{{ $projects->links() }}</div>
@else
    <div class="bg-white rounded-xl border border-dashed border-slate-300 py-16 text-center">
        <div class="w-14 h-14 mx-auto rounded-full bg-slate-100 grid place-items-center text-slate-300 mb-3">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
        </div>
        <p class="text-slate-500 font-medium">{{ __('app.no_projects_found') }}</p>
        <p class="text-slate-400 text-sm mt-1">{{ __('app.start_by_adding') }}</p>
        <a href="{{ route('projects.create') }}" class="inline-block mt-4 rounded-lg bg-brand-600 text-white text-sm font-semibold px-4 py-2.5">{{ __('app.new_project') }}</a>
    </div>
@endif
@endsection
