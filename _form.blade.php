@php
    $val = fn ($field, $default = '') => old($field, $project->$field ?? $default);
@endphp

@if ($errors->any())
    <div class="mb-5 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 text-sm">
        <ul class="list-disc {{ app()->getLocale()==='ar' ? 'pr-5' : 'pl-5' }} space-y-0.5">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-5 sm:p-6">
        <h3 class="font-bold text-slate-800 mb-5 pb-3 border-b border-slate-100">{{ __('app.basic_info') }}</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-slate-600 mb-1.5">{{ __('app.project_name') }} <span class="text-rose-500">*</span></label>
                <input type="text" name="name" value="{{ $val('name') }}" required class="w-full text-sm rounded-lg border border-slate-300 focus:border-brand-500 focus:ring-brand-500 px-3 py-2.5">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600 mb-1.5">{{ __('app.project_code') }}</label>
                <input type="text" name="code" value="{{ $val('code') }}" placeholder="PRJ-2026-001" class="w-full text-sm rounded-lg border border-slate-300 focus:border-brand-500 focus:ring-brand-500 px-3 py-2.5">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600 mb-1.5">{{ __('app.project_type') }}</label>
                <select name="type" class="w-full text-sm rounded-lg border border-slate-300 focus:border-brand-500 focus:ring-brand-500 px-3 py-2.5">
                    <option value="">{{ __('app.select') }}</option>
                    @foreach (\App\Models\Project::TYPES as $k => $lbl)
                        <option value="{{ $k }}" @selected((string) $val('type') === $k)>{{ __('app.'.$lbl) }}</option>
                    @endforeach
                </select>
                @if (! ($project->exists ?? false))<p class="text-[11px] text-slate-400 mt-1">{{ __('app.project_type_hint') }}</p>@endif
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600 mb-1.5">{{ __('app.client_name') }}</label>
                <input type="text" name="client_name" value="{{ $val('client_name') }}" class="w-full text-sm rounded-lg border border-slate-300 focus:border-brand-500 focus:ring-brand-500 px-3 py-2.5">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-slate-600 mb-1.5">{{ __('app.location') }}</label>
                <input type="text" name="location" value="{{ $val('location') }}" class="w-full text-sm rounded-lg border border-slate-300 focus:border-brand-500 focus:ring-brand-500 px-3 py-2.5">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-slate-600 mb-1.5">{{ __('app.description') }}</label>
                <textarea name="description" rows="3" class="w-full text-sm rounded-lg border border-slate-300 focus:border-brand-500 focus:ring-brand-500 px-3 py-2.5">{{ $val('description') }}</textarea>
            </div>
        </div>

        <h3 class="font-bold text-slate-800 mb-5 mt-8 pb-3 border-b border-slate-100">{{ __('app.team_and_dates') }}</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-600 mb-1.5">{{ __('app.project_manager') }}</label>
                <select name="project_manager_id" class="w-full text-sm rounded-lg border border-slate-300 focus:border-brand-500 focus:ring-brand-500 px-3 py-2.5">
                    <option value="">{{ __('app.select') }}</option>
                    @foreach ($managers as $m)
                        <option value="{{ $m->id }}" @selected((string) $val('project_manager_id') === (string) $m->id)>{{ $m->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600 mb-1.5">{{ __('app.supervisor') }}</label>
                <select name="supervisor_id" class="w-full text-sm rounded-lg border border-slate-300 focus:border-brand-500 focus:ring-brand-500 px-3 py-2.5">
                    <option value="">{{ __('app.select') }}</option>
                    @foreach ($managers as $m)
                        <option value="{{ $m->id }}" @selected((string) $val('supervisor_id') === (string) $m->id)>{{ $m->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600 mb-1.5">{{ __('app.start_date') }}</label>
                <input type="date" name="start_date" value="{{ $val('start_date') ? \Illuminate\Support\Carbon::parse($val('start_date'))->format('Y-m-d') : '' }}" class="w-full text-sm rounded-lg border border-slate-300 focus:border-brand-500 focus:ring-brand-500 px-3 py-2.5">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600 mb-1.5">{{ __('app.end_date') }}</label>
                <input type="date" name="end_date" value="{{ $val('end_date') ? \Illuminate\Support\Carbon::parse($val('end_date'))->format('Y-m-d') : '' }}" class="w-full text-sm rounded-lg border border-slate-300 focus:border-brand-500 focus:ring-brand-500 px-3 py-2.5">
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5 sm:p-6">
            <h3 class="font-bold text-slate-800 mb-5 pb-3 border-b border-slate-100">{{ __('app.status_and_progress') }}</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1.5">{{ __('app.status') }} <span class="text-rose-500">*</span></label>
                    <select name="status" required class="w-full text-sm rounded-lg border border-slate-300 focus:border-brand-500 focus:ring-brand-500 px-3 py-2.5">
                        @foreach (array_keys($statuses) as $key)
                            <option value="{{ $key }}" @selected((string) $val('status', 'active') === $key)>{{ __('app.status_'.$key) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1.5">{{ __('app.progress') }}: <span id="pv" class="font-bold text-brand-600">{{ $val('progress', 0) }}%</span></label>
                    <input type="range" min="0" max="100" name="progress" value="{{ $val('progress', 0) }}" oninput="document.getElementById('pv').textContent=this.value+'%'" class="w-full accent-brand-600">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 sm:p-6">
            <h3 class="font-bold text-slate-800 mb-5 pb-3 border-b border-slate-100">{{ __('app.finance_sar') }}</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1.5">{{ __('app.contract_value') }}</label>
                    <input type="number" step="0.01" min="0" name="contract_value" value="{{ $val('contract_value') }}" class="w-full text-sm rounded-lg border border-slate-300 focus:border-brand-500 focus:ring-brand-500 px-3 py-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1.5">{{ __('app.budget') }}</label>
                    <input type="number" step="0.01" min="0" name="budget" value="{{ $val('budget') }}" class="w-full text-sm rounded-lg border border-slate-300 focus:border-brand-500 focus:ring-brand-500 px-3 py-2.5">
                </div>
            </div>
        </div>
    </div>
</div>
