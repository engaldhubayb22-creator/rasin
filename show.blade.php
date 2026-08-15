@extends('layouts.app')
@section('title', $project->name)
@section('page-title', $project->name)

@section('content')
@php
    $fmt = fn ($n) => $n !== null ? number_format((float) $n, 2) : '—';
    $date = fn ($d) => $d ? $d->format('Y-m-d') : '—';
    $rtl = app()->getLocale()==='ar';
    $tabs = [
        'overview'    => ['tab_overview', true],
        'tasks'       => ['tab_tasks', true],
        'checklist'   => ['tab_checklist', true],
        'schedule'    => ['tab_schedule', true],
        'budget'      => ['tab_budget', true],
        'team'        => ['tab_team', true],
        'drawings'    => ['tab_drawings', false],
        'procurement' => ['tab_procurement', false],
        'approvals'   => ['tab_approvals', false],
    ];
    $money = fn ($n) => number_format((float) $n, 0);
    $spent = $project->budgetSpentPercent();
    $budgetColor = $project->isOverBudget() ? 'rose' : ($spent >= 85 ? 'amber' : 'emerald');
@endphp

<div class="mb-4 flex items-center gap-2 text-sm text-slate-400">
    <a href="{{ route('projects.index') }}" class="hover:text-brand-600">{{ __('app.projects') }}</a>
    <span>/</span><span class="text-slate-600">{{ $project->name }}</span>
</div>

{{-- ترويسة المشروع --}}
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden mb-5">
    <div class="p-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div class="flex items-start gap-4">
                <div class="w-14 h-14 rounded-xl bg-brand-50 text-brand-700 grid place-items-center text-2xl font-extrabold shrink-0">{{ mb_substr($project->name, 0, 1) }}</div>
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <h2 class="text-xl font-bold text-slate-800">{{ $project->name }}</h2>
                        <span class="text-xs px-2.5 py-1 rounded-full bg-{{ $project->statusColor() }}-50 text-{{ $project->statusColor() }}-700">{{ $project->statusLabel() }}</span>
                    </div>
                    <div class="text-sm text-slate-400 mt-1">{{ $project->code ?? '—' }}@if ($project->client_name) · {{ $project->client_name }} @endif</div>
                </div>
            </div>
            <a href="{{ route('projects.edit', $project) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium px-4 py-2.5 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                {{ __('app.edit') }}
            </a>
        </div>
        <div class="mt-5">
            <div class="flex justify-between text-sm mb-1.5"><span class="text-slate-500">{{ __('app.progress') }}</span><span class="font-bold text-slate-700">{{ $project->progress }}%</span></div>
            <div class="h-2.5 rounded-full bg-slate-100 overflow-hidden"><div class="h-full bg-brand-500" style="width: {{ $project->progress }}%"></div></div>
        </div>
    </div>

    {{-- شريط التبويبات --}}
    <div class="flex gap-1 px-3 py-2 border-t border-slate-200 overflow-x-auto text-sm">
        @foreach ($tabs as $key => [$label, $enabled])
            <button type="button" data-tab="{{ $key }}" onclick="showTab('{{ $key }}')"
                    class="tab-btn whitespace-nowrap px-3 py-2 rounded-lg {{ $loop->first ? 'bg-brand-50 text-brand-700 font-semibold' : 'text-slate-500 hover:bg-slate-50' }}">
                {{ __('app.'.$label) }}@unless($enabled)<span class="text-[10px] text-slate-300"> ·</span>@endunless
            </button>
        @endforeach
    </div>
</div>

{{-- ==================== نظرة عامة ==================== --}}
<div data-panel="overview" class="tab-panel">
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
        <div class="bg-white rounded-xl border border-slate-200 p-5"><div class="text-slate-400 text-xs">{{ __('app.open_tasks') }}</div><div class="text-2xl font-extrabold text-slate-800">{{ $taskStats['open'] }}</div></div>
        <div class="bg-white rounded-xl border border-slate-200 p-5"><div class="text-slate-400 text-xs">{{ __('app.schedule') }}</div><div class="text-2xl font-extrabold text-sky-600">{{ $project->activities->count() }}</div></div>
        <div class="bg-white rounded-xl border border-slate-200 p-5"><div class="text-slate-400 text-xs">{{ __('app.budget_spent') }}</div><div class="text-2xl font-extrabold text-{{ $budgetColor }}-600">{{ $spent }}%</div></div>
        <div class="bg-white rounded-xl border border-slate-200 p-5"><div class="text-slate-400 text-xs">{{ __('app.team') }}</div><div class="text-2xl font-extrabold text-brand-600">{{ $project->members->count() }}</div></div>
    </div>
    <div class="grid lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-bold text-slate-800 mb-4">{{ __('app.project_info') }}</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div><dt class="text-slate-400 mb-0.5">{{ __('app.client_name') }}</dt><dd class="text-slate-700 font-medium">{{ $project->client_name ?? '—' }}</dd></div>
                <div><dt class="text-slate-400 mb-0.5">{{ __('app.location') }}</dt><dd class="text-slate-700 font-medium">{{ $project->location ?? '—' }}</dd></div>
                <div><dt class="text-slate-400 mb-0.5">{{ __('app.start_date') }}</dt><dd class="text-slate-700 font-medium">{{ $date($project->start_date) }}</dd></div>
                <div><dt class="text-slate-400 mb-0.5">{{ __('app.end_date') }}</dt><dd class="text-slate-700 font-medium">{{ $date($project->end_date) }}</dd></div>
                <div><dt class="text-slate-400 mb-0.5">{{ __('app.project_manager') }}</dt><dd class="text-slate-700 font-medium">{{ $project->projectManager->name ?? '—' }}</dd></div>
                <div><dt class="text-slate-400 mb-0.5">{{ __('app.supervisor') }}</dt><dd class="text-slate-700 font-medium">{{ $project->supervisor->name ?? '—' }}</dd></div>
            </dl>
            @if ($project->description)
                <div class="mt-5 pt-5 border-t border-slate-100"><dt class="text-slate-400 text-sm mb-1">{{ __('app.description') }}</dt><dd class="text-slate-700 text-sm leading-relaxed whitespace-pre-line">{{ $project->description }}</dd></div>
            @endif
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-800">{{ __('app.finance_sar') }}</h3>
                <button type="button" onclick="showTab('budget')" class="text-xs text-brand-600 hover:text-brand-700 font-medium">{{ __('app.tab_budget') }} ←</button>
            </div>
            <div class="text-sm text-slate-400">{{ __('app.contract_value') }}</div>
            <div class="text-2xl font-extrabold text-slate-800 mt-1">{{ $fmt($project->contract_value) }} <span class="text-sm font-normal text-slate-400">{{ __('app.currency') }}</span></div>

            <div class="pt-4 mt-4 border-t border-slate-100">
                <div class="flex justify-between text-sm mb-1.5">
                    <span class="text-slate-400">{{ __('app.budget_spent') }}</span>
                    <span class="font-bold text-{{ $budgetColor }}-600">{{ $spent }}%</span>
                </div>
                <div class="h-2.5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-{{ $budgetColor }}-500" style="width: {{ min($spent, 100) }}%"></div>
                </div>
                <div class="flex justify-between text-xs text-slate-500 mt-2">
                    <span>{{ __('app.spent') }}: {{ $money($project->totalActual()) }}</span>
                    <span>{{ __('app.of') }} {{ $money($project->totalBudgeted()) }}</span>
                </div>
                <div class="mt-3 text-sm">
                    <span class="text-slate-400">{{ __('app.remaining') }}: </span>
                    <span class="font-bold text-{{ $project->budgetRemaining() < 0 ? 'rose' : 'slate' }}-700">{{ $money($project->budgetRemaining()) }} {{ __('app.currency') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ==================== المهام ==================== --}}
<div data-panel="tasks" class="tab-panel hidden">
    <div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
        <form method="POST" action="{{ route('tasks.store', $project) }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
            @csrf
            <div class="sm:col-span-5"><label class="block text-xs text-slate-500 mb-1">{{ __('app.task_title') }} *</label><input name="title" required class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2"></div>
            <div class="sm:col-span-3"><label class="block text-xs text-slate-500 mb-1">{{ __('app.assigned_to') }}</label>
                <select name="assigned_to" class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2"><option value="">{{ __('app.unassigned') }}</option>
                    @foreach ($managers as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
                </select></div>
            <div class="sm:col-span-2"><label class="block text-xs text-slate-500 mb-1">{{ __('app.priority') }}</label>
                <select name="priority" class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2">
                    @foreach (array_keys($priorities) as $k)<option value="{{ $k }}" @selected($k==='normal')>{{ __('app.priority_'.$k) }}</option>@endforeach
                </select></div>
            <div class="sm:col-span-2"><button class="w-full rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold py-2">{{ __('app.add') }}</button></div>
        </form>
    </div>

    @forelse ($project->tasks->sortBy('status') as $task)
        <div class="bg-white rounded-lg border border-slate-200 p-4 mb-2 flex items-center gap-3 flex-wrap">
            <span class="w-2 h-2 rounded-full bg-{{ $task->priorityColor() }}-500 shrink-0" title="{{ $task->priorityLabel() }}"></span>
            <div class="flex-1 min-w-[150px]">
                <div class="font-semibold text-slate-800 text-sm">{{ $task->title }}</div>
                <div class="text-xs text-slate-400">{{ $task->assignee->name ?? __('app.unassigned') }}@if($task->due_date) · {{ $task->due_date->format('Y-m-d') }} @endif</div>
            </div>
            <form method="POST" action="{{ route('tasks.update', $task) }}">
                @csrf @method('PATCH')
                <select name="status" onchange="this.form.submit()" class="text-xs rounded-lg border border-slate-300 px-2 py-1.5 bg-{{ $task->statusColor() }}-50 text-{{ $task->statusColor() }}-700">
                    @foreach (array_keys($taskStatuses) as $k)<option value="{{ $k }}" @selected($task->status===$k)>{{ __('app.task_'.$k) }}</option>@endforeach
                </select>
            </form>
            <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('{{ __('app.confirm_delete') }}')">
                @csrf @method('DELETE')
                <button class="p-1.5 text-slate-300 hover:text-rose-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
            </form>
        </div>
    @empty
        <div class="bg-white rounded-xl border border-dashed border-slate-300 py-10 text-center text-slate-400 text-sm">{{ __('app.no_tasks') }}</div>
    @endforelse
</div>

{{-- ==================== الجدول الزمني (نسخ) ==================== --}}
<div data-panel="schedule" class="tab-panel hidden">
    <div class="grid lg:grid-cols-3 gap-5">
        {{-- رفع نسخة --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                {{ __('app.upload_new_schedule') }}
            </h3>
            <form method="POST" action="{{ route('schedule.store', $project) }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs text-slate-500 mb-1">{{ __('app.version_name') }} *</label>
                    <input name="name" required placeholder="{{ __('app.version_name_hint') }}" class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">{{ __('app.schedule_file') }}</label>
                    <input type="file" name="source_file" accept=".mpp,.xlsx,.xls,.pdf,.csv" class="w-full text-xs text-slate-500 file:me-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-slate-700">
                    <p class="text-[11px] text-slate-400 mt-1">(.mpp, .xlsx, .pdf)</p>
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">{{ __('app.notes') }}</label>
                    <textarea name="notes" rows="2" class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2"></textarea>
                </div>
                <button class="w-full rounded-lg bg-brand-700 hover:bg-brand-800 text-white text-sm font-semibold py-2.5">{{ __('app.upload_and_read') }}</button>
            </form>
        </div>

        {{-- قائمة النسخ --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-100 font-bold text-slate-700 flex items-center gap-2">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                {{ __('app.schedule_versions') }} ({{ $project->scheduleVersions->count() }})
            </div>
            @if ($project->scheduleVersions->count())
                <table class="w-full text-sm {{ $rtl ? 'text-right' : 'text-left' }}">
                    <thead class="bg-slate-50 text-slate-500 text-xs">
                        <tr>
                            <th class="px-4 py-3">{{ __('app.version') }}</th>
                            <th class="px-4 py-3 text-center">{{ __('app.activities_count') }}</th>
                            <th class="px-4 py-3 text-center">{{ __('app.overall_progress') }}</th>
                            <th class="px-4 py-3">{{ __('app.status') }}</th>
                            <th class="px-4 py-3">{{ __('app.uploaded_by') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($project->scheduleVersions as $v)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('schedule.show', [$project, $v]) }}" class="font-semibold text-brand-700 hover:underline">{{ $v->name }}</a>
                                    @if ($v->source_file)<div class="text-[11px] text-slate-400">{{ $v->source_file }}</div>@endif
                                </td>
                                <td class="px-4 py-3 text-center text-slate-500">{{ $v->totalActivities() }}</td>
                                <td class="px-4 py-3 text-center font-semibold text-brand-700">{{ $v->overallPercent() }}%</td>
                                <td class="px-4 py-3"><span class="text-xs px-2.5 py-1 rounded-full bg-{{ $v->statusColor() }}-50 text-{{ $v->statusColor() }}-700">{{ $v->statusLabel() }}</span></td>
                                <td class="px-4 py-3 text-slate-500 text-xs">{{ $v->uploader->name ?? '—' }}<div class="text-slate-400">{{ $v->created_at?->format('Y-m-d') }}</div></td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('schedule.show', [$project, $v]) }}" class="text-xs text-brand-600 hover:text-brand-700 font-medium">{{ __('app.open') }} ←</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="py-12 text-center text-slate-400 text-sm">{{ __('app.no_schedule_versions') }}</div>
            @endif
        </div>
    </div>
</div>

{{-- ==================== الميزانية ==================== --}}
<div data-panel="budget" class="tab-panel hidden">
    {{-- بطاقات ملخص الميزانية --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-slate-400 text-xs">{{ __('app.total_budgeted') }}</div>
            <div class="text-xl font-extrabold text-slate-800 mt-1">{{ $money($project->totalBudgeted()) }}</div>
            <div class="text-xs text-slate-400">{{ __('app.currency') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-slate-400 text-xs">{{ __('app.total_committed') }}</div>
            <div class="text-xl font-extrabold text-sky-600 mt-1">{{ $money($project->totalCommitted()) }}</div>
            <div class="text-xs text-slate-400">{{ __('app.currency') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-slate-400 text-xs">{{ __('app.total_actual') }}</div>
            <div class="text-xl font-extrabold text-{{ $budgetColor }}-600 mt-1">{{ $money($project->totalActual()) }}</div>
            <div class="text-xs text-slate-400">{{ __('app.currency') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-slate-400 text-xs">{{ __('app.remaining') }}</div>
            <div class="text-xl font-extrabold text-{{ $project->budgetRemaining() < 0 ? 'rose' : 'emerald' }}-600 mt-1">{{ $money($project->budgetRemaining()) }}</div>
            <div class="text-xs text-slate-400">{{ __('app.currency') }}</div>
        </div>
    </div>

    {{-- شريط نسبة الصرف --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 mb-5">
        <div class="flex justify-between text-sm mb-1.5">
            <span class="text-slate-500">{{ __('app.budget_spent') }}</span>
            <span class="font-bold text-{{ $budgetColor }}-600">{{ $spent }}%
                @if($project->isOverBudget())<span class="text-xs bg-rose-50 text-rose-700 rounded-full px-2 py-0.5 {{ $rtl ? 'mr-1' : 'ml-1' }}">{{ __('app.over_budget') }}</span>@endif
            </span>
        </div>
        <div class="h-3 rounded-full bg-slate-100 overflow-hidden"><div class="h-full bg-{{ $budgetColor }}-500" style="width: {{ min($spent, 100) }}%"></div></div>
    </div>

    {{-- نموذج إضافة بند --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
        <form method="POST" action="{{ route('budget.store', $project) }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
            @csrf
            <div class="sm:col-span-3"><label class="block text-xs text-slate-500 mb-1">{{ __('app.budget_category') }}</label><input name="category" list="budget-categories" class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2"></div>
            <div class="sm:col-span-3"><label class="block text-xs text-slate-500 mb-1">{{ __('app.budget_item') }} *</label><input name="name" required class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2"></div>
            <div class="sm:col-span-2"><label class="block text-xs text-slate-500 mb-1">{{ __('app.budgeted_amount') }}</label><input type="number" step="0.01" min="0" name="budgeted_amount" value="0" class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2"></div>
            <div class="sm:col-span-2"><label class="block text-xs text-slate-500 mb-1">{{ __('app.committed_amount') }}</label><input type="number" step="0.01" min="0" name="committed_amount" value="0" class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2"></div>
            <div class="sm:col-span-2"><button class="w-full rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold py-2">{{ __('app.add') }}</button></div>
        </form>
        <datalist id="budget-categories">
            @foreach ($project->budgetItems->pluck('category')->filter()->unique() as $c)<option value="{{ $c }}">@endforeach
        </datalist>
    </div>

    {{-- جدول بنود الميزانية --}}
    @if ($project->budgetItems->count())
        <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
            <table class="w-full text-sm {{ $rtl ? 'text-right' : 'text-left' }}">
                <thead class="bg-slate-50 text-slate-500 text-xs">
                    <tr>
                        <th class="px-3 py-3">{{ __('app.budget_category') }} / {{ __('app.budget_item') }}</th>
                        <th class="px-3 py-3 text-center">{{ __('app.budgeted_amount') }}</th>
                        <th class="px-3 py-3 text-center">{{ __('app.committed_amount') }}</th>
                        <th class="px-3 py-3 text-center">{{ __('app.actual_amount') }}</th>
                        <th class="px-3 py-3 text-center">{{ __('app.remaining') }}</th>
                        <th class="px-3 py-3 text-center">{{ __('app.budget_spent') }}</th>
                        <th class="px-3 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($project->budgetItems as $b)
                        <tr class="{{ $b->isOverBudget() ? 'bg-rose-50/30' : '' }}">
                            <td class="px-3 py-3">
                                <div class="font-medium text-slate-800">{{ $b->displayName() }}</div>
                                <div class="text-xs text-slate-400">{{ $b->category ?? '' }}</div>
                            </td>
                            <td class="px-3 py-3 text-center text-slate-600">{{ $money($b->budgeted_amount) }}</td>
                            <td class="px-3 py-3 text-center text-slate-500">{{ $money($b->committed_amount) }}</td>
                            <td class="px-3 py-3 text-center font-medium text-slate-700">{{ $money($b->actual_amount) }}</td>
                            <td class="px-3 py-3 text-center"><span class="font-semibold text-{{ $b->remaining() < 0 ? 'rose' : 'emerald' }}-600">{{ $money($b->remaining()) }}</span></td>
                            <td class="px-3 py-3 text-center">
                                <div class="flex items-center gap-2 justify-center">
                                    <div class="w-14 h-1.5 rounded-full bg-slate-100 overflow-hidden"><div class="h-full bg-{{ $b->healthColor() }}-500" style="width: {{ min($b->spentPercent(), 100) }}%"></div></div>
                                    <span class="text-{{ $b->healthColor() }}-600 font-medium">{{ $b->spentPercent() }}%</span>
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-1">
                                    <button type="button" onclick="document.getElementById('bud-{{ $b->id }}').classList.toggle('hidden')" class="p-1.5 text-slate-300 hover:text-brand-600" title="{{ __('app.update') }}"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                                    <form method="POST" action="{{ route('budget.destroy', $b) }}" onsubmit="return confirm('{{ __('app.confirm_delete') }}')">@csrf @method('DELETE')<button class="p-1.5 text-slate-300 hover:text-rose-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 7h12M9 7V5h6v2m-7 0l1 12h6l1-12"/></svg></button></form>
                                </div>
                            </td>
                        </tr>
                        <tr id="bud-{{ $b->id }}" class="hidden bg-slate-50">
                            <td colspan="7" class="px-3 py-3">
                                <form method="POST" action="{{ route('budget.update', $b) }}" class="flex flex-wrap items-end gap-3">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="name" value="{{ $b->name }}">
                                    <div><label class="block text-xs text-slate-500 mb-1">{{ __('app.budgeted_amount') }}</label><input type="number" step="0.01" min="0" name="budgeted_amount" value="{{ $b->budgeted_amount }}" class="w-32 text-sm rounded-lg border border-slate-300 px-2 py-1.5"></div>
                                    <div><label class="block text-xs text-slate-500 mb-1">{{ __('app.committed_amount') }}</label><input type="number" step="0.01" min="0" name="committed_amount" value="{{ $b->committed_amount }}" class="w-32 text-sm rounded-lg border border-slate-300 px-2 py-1.5"></div>
                                    <div><label class="block text-xs text-slate-500 mb-1">{{ __('app.actual_amount') }}</label><input type="number" step="0.01" min="0" name="actual_amount" value="{{ $b->actual_amount }}" class="w-32 text-sm rounded-lg border border-slate-300 px-2 py-1.5"></div>
                                    <button class="rounded-lg bg-slate-800 text-white text-sm px-4 py-1.5">{{ __('app.save') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50 font-bold text-slate-700 text-xs">
                    <tr>
                        <td class="px-3 py-3">{{ __('app.total') }}</td>
                        <td class="px-3 py-3 text-center">{{ $money($project->totalBudgeted()) }}</td>
                        <td class="px-3 py-3 text-center">{{ $money($project->totalCommitted()) }}</td>
                        <td class="px-3 py-3 text-center">{{ $money($project->totalActual()) }}</td>
                        <td class="px-3 py-3 text-center text-{{ $project->budgetRemaining() < 0 ? 'rose' : 'emerald' }}-600">{{ $money($project->budgetRemaining()) }}</td>
                        <td class="px-3 py-3 text-center text-{{ $budgetColor }}-600">{{ $spent }}%</td>
                        <td class="px-3 py-3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @else
        <div class="bg-white rounded-xl border border-dashed border-slate-300 py-10 text-center text-slate-400 text-sm">{{ __('app.no_budget_items') }}</div>
    @endif
</div>

{{-- ==================== الفريق ==================== --}}
<div data-panel="team" class="tab-panel hidden">
    <div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
        <form method="POST" action="{{ route('members.store', $project) }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
            @csrf
            <div class="sm:col-span-5"><label class="block text-xs text-slate-500 mb-1">{{ __('app.member') }} *</label>
                <select name="user_id" required class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2"><option value="">{{ __('app.select') }}</option>
                    @foreach ($managers as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
                </select></div>
            <div class="sm:col-span-4"><label class="block text-xs text-slate-500 mb-1">{{ __('app.team_role') }}</label><input name="team_role" class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2"></div>
            <div class="sm:col-span-2 flex items-center h-[38px]"><label class="flex items-center gap-1.5 text-sm text-slate-600"><input type="checkbox" name="is_primary" value="1" class="rounded border-slate-300 text-brand-600">{{ __('app.primary') }}</label></div>
            <div class="sm:col-span-1"><button class="w-full rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold py-2">{{ __('app.add') }}</button></div>
        </form>
    </div>

    @forelse ($project->members as $member)
        <div class="bg-white rounded-lg border border-slate-200 p-4 mb-2 flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-brand-50 text-brand-700 grid place-items-center font-bold">{{ mb_substr($member->user->name ?? '?', 0, 1) }}</div>
            <div class="flex-1">
                <div class="font-semibold text-slate-800 text-sm">{{ $member->user->name ?? '—' }} @if($member->is_primary)<span class="text-[10px] bg-brand-50 text-brand-700 rounded px-1.5 py-0.5">{{ __('app.primary') }}</span>@endif</div>
                <div class="text-xs text-slate-400">{{ $member->team_role ?? '—' }}</div>
            </div>
            <form method="POST" action="{{ route('members.destroy', [$project, $member]) }}" onsubmit="return confirm('{{ __('app.confirm_delete') }}')">@csrf @method('DELETE')
                <button class="text-xs text-rose-600 hover:text-rose-700 px-2 py-1">{{ __('app.remove') }}</button>
            </form>
        </div>
    @empty
        <div class="bg-white rounded-xl border border-dashed border-slate-300 py-10 text-center text-slate-400 text-sm">{{ __('app.no_members') }}</div>
    @endforelse
</div>

{{-- ==================== التشك لست ==================== --}}
<div data-panel="checklist" class="tab-panel hidden">
    @include('projects.partials.checklist')
</div>

{{-- ==================== تبويبات قادمة ==================== --}}
@foreach (['drawings','procurement','approvals'] as $ph)
    <div data-panel="{{ $ph }}" class="tab-panel hidden">
        <div class="bg-slate-50 rounded-xl border border-dashed border-slate-300 py-16 text-center">
            <div class="text-4xl mb-3">🚧</div>
            <p class="text-slate-500 font-medium">{{ __('app.tab_'.$ph) }}</p>
            <p class="text-slate-400 text-sm mt-1">{{ __('app.coming_soon') }}</p>
        </div>
    </div>
@endforeach

@push('scripts')
<script>
    function showTab(key) {
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.toggle('hidden', p.dataset.panel !== key));
        document.querySelectorAll('.tab-btn').forEach(b => {
            const on = b.dataset.tab === key;
            b.classList.toggle('bg-brand-50', on);
            b.classList.toggle('text-brand-700', on);
            b.classList.toggle('font-semibold', on);
            b.classList.toggle('text-slate-500', !on);
        });
        if (history.replaceState) history.replaceState(null, '', '#' + key);
    }
    // فتح التبويب من الرابط (#tasks مثلاً)
    document.addEventListener('DOMContentLoaded', () => {
        const h = (location.hash || '').replace('#','');
        if (h && document.querySelector(`[data-panel="${h}"]`)) showTab(h);
    });
</script>
@endpush
@endsection
