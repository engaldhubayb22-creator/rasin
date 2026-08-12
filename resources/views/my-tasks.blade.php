@extends('layouts.app')
@section('title', __('app.my_tasks'))
@section('page-title', __('app.my_tasks'))

@section('content')
<p class="text-sm text-slate-400 mb-5">{{ __('app.my_tasks_subtitle') }}</p>

@php $order = ['in_progress','pending','blocked','completed']; @endphp

@php $hasAny = collect($tasksByStatus)->flatten()->count() > 0; @endphp

@if ($hasAny)
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
