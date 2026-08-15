@extends('layouts.app')
@section('title', __('app.my_tasks'))
@section('page-title', __('app.my_tasks'))

@section('content')
<p class="text-sm text-slate-400 mb-5">{{ __('app.my_tasks_subtitle') }}</p>

{{-- المتطلبات المسندة إليّ (مرتبطة من متابعة المتطلبات) --}}
@if ($myRequirements->count())
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden mb-5">
        <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-bold text-slate-800 flex items-center gap-2">📋 {{ __('app.my_requirements') }}</h2>
            <a href="{{ route('requirements.index') }}" class="text-xs text-brand-600 hover:text-brand-700 font-medium">{{ __('app.view_all') }}</a>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach ($myRequirements as $r)
                <div class="px-5 py-3 flex items-center gap-3 flex-wrap {{ $r->isOverdue() ? 'bg-rose-50/30' : '' }}">
                    <div class="flex-1 min-w-[160px]">
                        <div class="font-semibold text-slate-800 text-sm">{{ $r->title }}</div>
                        <div class="text-xs text-slate-400">{{ $r->project->name ?? '—' }}@if($r->due_date) · <span class="{{ $r->isOverdue() ? 'text-rose-600 font-semibold' : '' }}">{{ $r->due_date->format('Y-m-d') }}</span>@endif</div>
                    </div>
                    <span class="text-xs px-2 py-1 rounded-full bg-{{ $r->statusColor() }}-50 text-{{ $r->statusColor() }}-700">{{ $r->statusLabel() }}</span>
                </div>
            @endforeach
        </div>
    </div>
@endif

@php $order = ['in_progress','pending','blocked','completed']; @endphp

@php $hasAny = collect($tasksByStatus)->flatten()->count() > 0; @endphp

@if ($hasAny || $myRequirements->count())
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        @foreach ($order as $status)
            @php $group = $tasksByStatus[$status] ?? collect(); @endphp
            @continue($group->isEmpty())
            @php $color = ['in_progress'=>'sky','pending'=>'slate','blocked'=>'rose','completed'=>'emerald'][$status]; @endphp
            <div class="bg-white rounded-xl border border-slate-200">
                <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="font-bold text-slate-800 flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-{{ $color }}-500"></span>{{ __('app.task_'.$status) }}</h2>
                    <span class="text-xs text-slate-400">{{ $group->count() }}</span>
                </div>
                <div class="divide-y divide-slate-100">
                    @foreach ($group as $task)
                        <a href="{{ route('projects.show', $task->project) }}#tasks" class="block px-5 py-3.5 hover:bg-slate-50">
                            <div class="font-semibold text-slate-800 text-sm">{{ $task->title }}</div>
                            <div class="text-xs text-slate-400 mt-0.5 flex items-center gap-2 flex-wrap">
                                <span>{{ $task->project->name ?? '—' }}</span>
                                @if($task->due_date)<span>· {{ $task->due_date->format('Y-m-d') }}</span>@endif
                                <span class="px-1.5 py-0.5 rounded bg-{{ $task->priorityColor() }}-50 text-{{ $task->priorityColor() }}-700">{{ $task->priorityLabel() }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="bg-white rounded-xl border border-dashed border-slate-300 py-16 text-center">
        <div class="text-4xl mb-3">✅</div>
        <p class="text-slate-500 font-medium">{{ __('app.no_my_tasks') }}</p>
    </div>
@endif
@endsection
