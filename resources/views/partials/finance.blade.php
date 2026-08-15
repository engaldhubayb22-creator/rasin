@php
    $money = fn ($n) => number_format((float) $n);
    $rtl = app()->getLocale() === 'ar';
    $totalBudgeted = collect($projectsFinance)->sum('budgeted');
@endphp

{{-- بطاقات مالية --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-slate-400 text-xs">{{ __('app.total_budgeted') }}</div>
        <div class="text-xl font-extrabold text-slate-800 mt-1">{{ $money($totalBudgeted) }}</div>
        <div class="text-xs text-slate-400">{{ __('app.currency') }}</div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-slate-400 text-xs">{{ __('app.financial_commitments') }}</div>
        <div class="text-xl font-extrabold text-sky-600 mt-1">{{ $money($fin['total_invoiced']) }}</div>
        <div class="text-xs text-slate-400">{{ __('app.currency') }}</div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-slate-400 text-xs">{{ __('app.total_paid') }}</div>
        <div class="text-xl font-extrabold text-emerald-600 mt-1">{{ $money($fin['total_paid']) }}</div>
        <div class="text-xs text-slate-400">{{ __('app.currency') }}</div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-slate-400 text-xs">{{ __('app.remaining_to_pay') }}</div>
        <div class="text-xl font-extrabold text-rose-600 mt-1">{{ $money($fin['remaining_to_pay']) }}</div>
        <div class="text-xs text-slate-400">{{ __('app.currency') }} · {{ __('app.payment_rate') }} {{ $fin['payment_rate'] }}%</div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-5">
    {{-- جدول ميزانيات المشاريع --}}
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-100 font-bold text-slate-700">▤ {{ __('app.budget_by_project') }}</div>
        @if (collect($projectsFinance)->count())
            <div class="overflow-x-auto">
                <table class="w-full text-sm {{ $rtl ? 'text-right' : 'text-left' }}">
                    <thead class="bg-slate-50 text-slate-500 text-xs">
                        <tr>
                            <th class="px-4 py-3">{{ __('app.project') }}</th>
                            <th class="px-4 py-3 text-center">{{ __('app.budgeted_amount') }}</th>
                            <th class="px-4 py-3 text-center">{{ __('app.actual_amount') }}</th>
                            <th class="px-4 py-3 text-center">{{ __('app.remaining') }}</th>
                            <th class="px-4 py-3 text-center">{{ __('app.budget_spent') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($projectsFinance as $pf)
                            @php $hc = $pf['over'] ? 'rose' : ($pf['spent'] >= 85 ? 'amber' : 'emerald'); @endphp
                            <tr class="{{ $pf['over'] ? 'bg-rose-50/30' : '' }}">
                                <td class="px-4 py-2.5 font-medium text-slate-800">{{ $pf['name'] }}</td>
                                <td class="px-4 py-2.5 text-center text-slate-600">{{ $money($pf['budgeted']) }}</td>
                                <td class="px-4 py-2.5 text-center text-slate-700">{{ $money($pf['actual']) }}</td>
                                <td class="px-4 py-2.5 text-center font-semibold text-{{ $pf['remaining'] < 0 ? 'rose' : 'emerald' }}-600">{{ $money($pf['remaining']) }}</td>
                                <td class="px-4 py-2.5 text-center">
                                    <div class="flex items-center gap-2 justify-center">
                                        <div class="w-14 h-1.5 rounded-full bg-slate-100 overflow-hidden"><div class="h-full bg-{{ $hc }}-500" style="width: {{ min($pf['spent'], 100) }}%"></div></div>
                                        <span class="text-{{ $hc }}-600 font-medium">{{ $pf['spent'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="py-10 text-center text-slate-400 text-sm">{{ __('app.no_projects_found') }}</div>
        @endif
    </div>

    {{-- التدفق النقدي --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden h-fit">
        <div class="px-5 py-3.5 bg-emerald-50/60 border-b border-slate-100 font-bold text-slate-700">▤ {{ __('app.cash_flow') }}</div>
        <div class="p-5 space-y-3 text-sm">
            <div>
                <div class="flex justify-between mb-1"><span class="text-slate-500">{{ __('app.payment_rate') }}</span><span class="font-bold text-slate-700">{{ $fin['payment_rate'] }}%</span></div>
                <div class="h-2 rounded-full bg-slate-100 overflow-hidden"><div class="h-full bg-emerald-500" style="width: {{ min($fin['payment_rate'], 100) }}%"></div></div>
            </div>
            <div class="flex justify-between pt-2 border-t border-slate-100"><span class="text-slate-500">{{ __('app.total_invoiced') }}</span><span class="font-bold text-slate-700">{{ $money($fin['total_invoiced']) }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">{{ __('app.total_paid') }}</span><span class="font-bold text-emerald-600">{{ $money($fin['total_paid']) }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">{{ __('app.remaining_to_pay') }}</span><span class="font-bold text-rose-600">{{ $money($fin['remaining_to_pay']) }}</span></div>
        </div>
    </div>
</div>
