@extends('layouts.app')
@section('title', __('app.reports'))
@section('page-title', __('app.reports'))

@section('content')
@php
    $rtl = app()->getLocale() === 'ar';
    $money = fn ($n) => number_format((float) $n);
    $date = fn ($d) => $d ? $d->format('Y-m-d') : '—';
    $tabs = ['completed' => 'report_completed', 'active' => 'report_active', 'finance' => 'report_finance', 'delayed' => 'report_delayed'];
@endphp

<div class="mb-5">
    <h2 class="text-xl font-bold text-slate-800">{{ __('app.reports') }}</h2>
    <p class="text-sm text-slate-400">{{ __('app.reports_subtitle') }}</p>
</div>

{{-- تبويبات التقارير --}}
<div class="flex gap-1 mb-5 bg-white rounded-xl border border-slate-200 p-1.5 w-fit overflow-x-auto">
    @foreach ($tabs as $key => $label)
        <button type="button" data-rep="{{ $key }}" onclick="showReport('{{ $key }}')"
                class="rep-btn whitespace-nowrap px-4 py-2 rounded-lg text-sm {{ $loop->first ? 'bg-brand-600 text-white font-semibold' : 'text-slate-500 hover:bg-slate-50' }}">
            {{ __('app.'.$label) }}
        </button>
    @endforeach
</div>

{{-- ١) المشاريع المنجزة --}}
<div data-rep-panel="completed" class="rep-panel">
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-100 font-bold text-slate-700">✔ {{ __('app.report_completed') }} ({{ $completed->count() }})</div>
        @if ($completed->count())
            <div class="overflow-x-auto"><table class="w-full text-sm {{ $rtl ? 'text-right' : 'text-left' }}">
                <thead class="bg-slate-50 text-slate-500 text-xs"><tr>
                    <th class="px-4 py-3">{{ __('app.project') }}</th><th class="px-4 py-3">{{ __('app.client_name') }}</th>
                    <th class="px-4 py-3 text-center">{{ __('app.contract_value') }}</th><th class="px-4 py-3 text-center">{{ __('app.start_date') }}</th>
                    <th class="px-4 py-3 text-center">{{ __('app.end_date') }}</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($completed as $p)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-2.5"><a href="{{ route('projects.show', $p) }}" class="font-medium text-brand-700 hover:underline">{{ $p->name }}</a><div class="text-xs text-slate-400">{{ $p->code }}</div></td>
                            <td class="px-4 py-2.5 text-slate-600">{{ $p->client_name ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-center">{{ $money($p->contract_value) }}</td>
                            <td class="px-4 py-2.5 text-center text-slate-500">{{ $date($p->start_date) }}</td>
                            <td class="px-4 py-2.5 text-center text-slate-500">{{ $date($p->end_date) }}</td>
                        </tr>
                    @endforeach
                </tbody></table></div>
        @else
            <div class="py-10 text-center text-slate-400 text-sm">{{ __('app.no_completed_projects') }}</div>
        @endif
    </div>
</div>

{{-- ٢) حالة المشاريع النشطة --}}
<div data-rep-panel="active" class="rep-panel hidden">
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-100 font-bold text-slate-700">◴ {{ __('app.report_active') }} ({{ $active->count() }})</div>
        @if ($active->count())
            <div class="overflow-x-auto"><table class="w-full text-sm {{ $rtl ? 'text-right' : 'text-left' }}">
                <thead class="bg-slate-50 text-slate-500 text-xs"><tr>
                    <th class="px-4 py-3">{{ __('app.project') }}</th><th class="px-4 py-3">{{ __('app.project_manager') }}</th>
                    <th class="px-4 py-3 text-center">{{ __('app.progress') }}</th><th class="px-4 py-3 w-40">{{ __('app.progress_bar') }}</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($active as $p)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-2.5"><a href="{{ route('projects.show', $p) }}" class="font-medium text-brand-700 hover:underline">{{ $p->name }}</a><div class="text-xs text-slate-400">{{ $p->code }}</div></td>
                            <td class="px-4 py-2.5 text-slate-600">{{ $p->projectManager->name ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-center font-semibold text-brand-700">{{ $p->progress }}%</td>
                            <td class="px-4 py-2.5"><div class="h-2 rounded-full bg-slate-100 overflow-hidden"><div class="h-full bg-brand-500" style="width: {{ $p->progress }}%"></div></div></td>
                        </tr>
                    @endforeach
                </tbody></table></div>
        @else
            <div class="py-10 text-center text-slate-400 text-sm">{{ __('app.no_projects_found') }}</div>
        @endif
    </div>
</div>

{{-- ٣) ملخّص مالي --}}
<div data-rep-panel="finance" class="rep-panel hidden">
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-100 font-bold text-slate-700">▤ {{ __('app.report_finance') }}</div>
        <div class="overflow-x-auto"><table class="w-full text-sm {{ $rtl ? 'text-right' : 'text-left' }}">
            <thead class="bg-slate-50 text-slate-500 text-xs"><tr>
                <th class="px-4 py-3">{{ __('app.project') }}</th><th class="px-4 py-3">{{ __('app.status') }}</th>
                <th class="px-4 py-3 text-center">{{ __('app.budgeted_amount') }}</th><th class="px-4 py-3 text-center">{{ __('app.actual_amount') }}</th>
                <th class="px-4 py-3 text-center">{{ __('app.remaining') }}</th><th class="px-4 py-3 text-center">{{ __('app.budget_spent') }}</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($finance as $f)
                    @php $hc = $f['over'] ? 'rose' : ($f['spent'] >= 85 ? 'amber' : 'emerald'); @endphp
                    <tr class="{{ $f['over'] ? 'bg-rose-50/30' : '' }}">
                        <td class="px-4 py-2.5 font-medium text-slate-800">{{ $f['name'] }}</td>
                        <td class="px-4 py-2.5"><span class="text-xs px-2 py-1 rounded-full bg-{{ $f['statusColor'] }}-50 text-{{ $f['statusColor'] }}-700">{{ $f['status'] }}</span></td>
                        <td class="px-4 py-2.5 text-center text-slate-600">{{ $money($f['budgeted']) }}</td>
                        <td class="px-4 py-2.5 text-center text-slate-700">{{ $money($f['actual']) }}</td>
                        <td class="px-4 py-2.5 text-center font-semibold text-{{ $f['remaining'] < 0 ? 'rose' : 'emerald' }}-600">{{ $money($f['remaining']) }}</td>
                        <td class="px-4 py-2.5 text-center text-{{ $hc }}-600 font-medium">{{ $f['spent'] }}%</td>
                    </tr>
                @endforeach
            </tbody></table></div>
    </div>
</div>

{{-- ٤) الأنشطة المتأخرة --}}
<div data-rep-panel="delayed" class="rep-panel hidden">
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-100 font-bold text-rose-700">⚠ {{ __('app.report_delayed') }} ({{ $delayed->count() }})</div>
        @if ($delayed->count())
            <div class="overflow-x-auto"><table class="w-full text-sm {{ $rtl ? 'text-right' : 'text-left' }}">
                <thead class="bg-slate-50 text-slate-500 text-xs"><tr>
                    <th class="px-4 py-3">WBS</th><th class="px-4 py-3">{{ __('app.activity') }}</th><th class="px-4 py-3">{{ __('app.project') }}</th>
                    <th class="px-4 py-3 text-center">{{ __('app.delay') }}</th><th class="px-4 py-3 text-center">{{ __('app.progress') }}</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($delayed as $a)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-2.5 text-slate-400 text-xs">{{ $a->wbs }}</td>
                            <td class="px-4 py-2.5 text-slate-700">{{ $a->displayName() }}@if($a->is_critical)<span class="text-rose-500 text-xs"> ⚠</span>@endif</td>
                            <td class="px-4 py-2.5 text-slate-500 text-xs">{{ $a->version->project->name ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-center font-semibold text-rose-600">{{ $a->delay_days }}</td>
                            <td class="px-4 py-2.5 text-center text-slate-600">{{ $a->percent }}%</td>
                        </tr>
                    @endforeach
                </tbody></table></div>
        @else
            <div class="py-10 text-center text-slate-400 text-sm">{{ __('app.no_delayed') }}</div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    function showReport(key) {
        document.querySelectorAll('.rep-panel').forEach(p => p.classList.toggle('hidden', p.dataset.repPanel !== key));
        document.querySelectorAll('.rep-btn').forEach(b => {
            const on = b.dataset.rep === key;
            b.classList.toggle('bg-brand-600', on);
            b.classList.toggle('text-white', on);
            b.classList.toggle('font-semibold', on);
            b.classList.toggle('text-slate-500', !on);
        });
    }
</script>
@endpush
@endsection
