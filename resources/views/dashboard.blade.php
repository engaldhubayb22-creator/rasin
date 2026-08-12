@extends('layouts.app')
@section('title', __('app.exec_dashboard'))
@section('page-title', __('app.exec_dashboard'))

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n);
    $rtl = app()->getLocale() === 'ar';
    $roles = ['exec' => 'role_exec', 'pm' => 'role_pm', 'procurement' => 'role_procurement', 'mine' => 'role_mine'];
@endphp

{{-- الترويسة + تبويبات الأدوار --}}
<div class="mb-5">
    <div class="flex items-center gap-2 mb-1">
        <h2 class="text-xl font-bold text-slate-800">{{ __('app.exec_dashboard') }}</h2>
    </div>
    <p class="text-sm text-slate-400">{{ __('app.exec_subtitle') }} · {{ now()->translatedFormat('l، j F Y') }}</p>
</div>

<div class="flex gap-1 mb-5 bg-white rounded-xl border border-slate-200 p-1.5 w-fit overflow-x-auto">
    @foreach ($roles as $key => $label)
        <button type="button" data-rtab="{{ $key }}" onclick="showRole('{{ $key }}')"
                class="role-btn whitespace-nowrap px-4 py-2 rounded-lg text-sm {{ $loop->first ? 'bg-brand-600 text-white font-semibold' : 'text-slate-500 hover:bg-slate-50' }}">
            {{ __('app.'.$label) }}
        </button>
    @endforeach
</div>

{{-- ==================== المدير التنفيذي ==================== --}}
<div data-role="exec" class="role-panel">
    {{-- بطاقات المؤشرات المالية --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-slate-400 text-xs">{{ __('app.active_projects_value') }}</div>
            <div class="text-2xl font-extrabold text-slate-800 mt-1">{{ $fmt($fin['active_value']) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ __('app.currency') }} · {{ $fin['active_count'] }} {{ __('app.project') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-slate-400 text-xs">{{ __('app.total_paid') }}</div>
            <div class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $fmt($fin['total_paid']) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ __('app.currency') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-slate-400 text-xs">{{ __('app.financial_commitments') }}</div>
            <div class="text-2xl font-extrabold text-rose-600 mt-1">{{ $fmt($fin['commitments']) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ __('app.commitments_sub') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-slate-400 text-xs">{{ __('app.remaining_to_pay') }}</div>
            <div class="text-2xl font-extrabold text-slate-800 mt-1">{{ $fmt($fin['remaining_to_pay']) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ __('app.currency') }} · {{ __('app.payment_rate') }} {{ $fin['payment_rate'] }}%</div>
        </div>
    </div>

    {{-- صحة المحفظة --}}
    <div class="mb-5">
        <h3 class="flex items-center gap-2 font-bold text-slate-700 mb-3"><span class="text-brand-600">◎</span> {{ __('app.portfolio_health') }}</h3>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @php
                $hcards = [
                    ['on_track', 'emerald', '●'],
                    ['slight', 'amber', '●'],
                    ['at_risk', 'orange', '●'],
                    ['critical', 'rose', '●'],
                ];
            @endphp
            @foreach ($hcards as [$k, $color, $dot])
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <div class="text-slate-400 text-xs flex items-center gap-1.5"><span class="text-{{ $color }}-500">{{ $dot }}</span> {{ __('app.health_'.$k) }}</div>
                    <div class="text-2xl font-extrabold text-{{ $color }}-600 mt-1">{{ $health[$k] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- التدفق النقدي + أكبر الموردين --}}
    <div class="grid lg:grid-cols-2 gap-5 mb-5">
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3.5 bg-emerald-50/60 border-b border-slate-100 font-bold text-slate-700 flex items-center gap-2"><span>▤</span> {{ __('app.cash_flow') }}</div>
            <div class="p-5 space-y-3 text-sm">
                <div>
                    <div class="flex justify-between mb-1"><span class="text-slate-500">{{ __('app.payment_rate') }}</span><span class="font-bold text-slate-700">{{ $fin['payment_rate'] }}%</span></div>
                    <div class="h-2 rounded-full bg-slate-100 overflow-hidden"><div class="h-full bg-emerald-500" style="width: {{ min($fin['payment_rate'], 100) }}%"></div></div>
                </div>
                <div class="flex justify-between pt-2 border-t border-slate-100"><span class="text-slate-500">{{ __('app.total_invoiced') }}</span><span class="font-bold text-slate-700">{{ $fmt($fin['total_invoiced']) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">{{ __('app.total_paid') }}</span><span class="font-bold text-emerald-600">{{ $fmt($fin['total_paid']) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">{{ __('app.remaining_to_pay') }}</span><span class="font-bold text-rose-600">{{ $fmt($fin['remaining_to_pay']) }}</span></div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-100 font-bold text-slate-700 flex items-center gap-2"><span>▤</span> {{ __('app.top_vendors') }}</div>
            @if ($topVendors->count())
                <table class="w-full text-sm {{ $rtl ? 'text-right' : 'text-left' }}">
                    <thead class="bg-slate-50 text-slate-500 text-xs"><tr><th class="px-4 py-2.5">{{ __('app.vendor') }}</th><th class="px-4 py-2.5 text-center">{{ __('app.pos') }}</th><th class="px-4 py-2.5">{{ __('app.value') }}</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($topVendors as $v)
                            <tr><td class="px-4 py-2.5">{{ $v['name'] }}</td><td class="px-4 py-2.5 text-center text-slate-500">{{ $v['pos'] }}</td><td class="px-4 py-2.5 font-medium">{{ $fmt($v['value']) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="py-10 text-center text-slate-400 text-sm">{{ __('app.no_vendor_data') }}</div>
            @endif
        </div>
    </div>

    {{-- أكبر العملاء + الاتجاه الشهري --}}
    <div class="grid lg:grid-cols-2 gap-5">
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-100 font-bold text-slate-700 flex items-center gap-2"><span>▤</span> {{ __('app.top_clients') }}</div>
            @if ($topClients->count())
                <table class="w-full text-sm {{ $rtl ? 'text-right' : 'text-left' }}">
                    <thead class="bg-slate-50 text-slate-500 text-xs"><tr><th class="px-4 py-2.5">{{ __('app.client') }}</th><th class="px-4 py-2.5 text-center">{{ __('app.projects_count') }}</th><th class="px-4 py-2.5">{{ __('app.value') }}</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($topClients as $c)
                            <tr><td class="px-4 py-2.5">{{ $c['name'] }}</td><td class="px-4 py-2.5 text-center text-slate-500">{{ $c['projects'] }}</td><td class="px-4 py-2.5 font-medium">{{ $fmt($c['value']) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="py-10 text-center text-slate-400 text-sm">{{ __('app.no_projects_found') }}</div>
            @endif
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-bold text-slate-700 mb-4 flex items-center gap-2"><span>▨</span> {{ __('app.monthly_trend') }}</h3>
            <div class="flex items-end justify-between gap-2 h-44">
                @foreach ($trend as $t)
                    @php $h = $trendMax > 0 ? max(round($t['value'] / $trendMax * 100), 2) : 2; @endphp
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <div class="text-[10px] text-slate-400">{{ $t['value'] >= 1000 ? round($t['value']/1000).'K' : $fmt($t['value']) }}</div>
                        <div class="w-full rounded-t bg-brand-500 hover:bg-brand-600 transition" style="height: {{ $h }}%"></div>
                        <div class="text-[10px] text-slate-400 whitespace-nowrap">{{ $t['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ==================== مدير المشاريع ==================== --}}
<div data-role="pm" class="role-panel hidden">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
        <div class="bg-white rounded-xl border border-slate-200 p-5"><div class="text-3xl font-extrabold text-slate-800">{{ $fmt($stats['total']) }}</div><div class="text-sm text-slate-400 mt-1">{{ __('app.total_projects') }}</div></div>
        <div class="bg-white rounded-xl border border-slate-200 p-5"><div class="text-3xl font-extrabold text-emerald-600">{{ $fmt($stats['active']) }}</div><div class="text-sm text-slate-400 mt-1">{{ __('app.active_projects') }}</div></div>
        <div class="bg-white rounded-xl border border-slate-200 p-5"><div class="text-3xl font-extrabold text-sky-600">{{ $fmt($stats['completed']) }}</div><div class="text-sm text-slate-400 mt-1">{{ __('app.completed_projects') }}</div></div>
        <div class="bg-white rounded-xl border border-slate-200 p-5"><div class="text-3xl font-extrabold text-amber-600">{{ $fmt($stats['on_hold']) }}</div><div class="text-sm text-slate-400 mt-1">{{ __('app.on_hold_projects') }}</div></div>
    </div>
    <div class="grid lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <h2 class="font-bold text-slate-800">{{ __('app.recent_projects') }}</h2>
                <a href="{{ route('projects.index') }}" class="text-sm text-brand-600 hover:text-brand-700 font-medium">{{ __('app.view_all') }}</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($recentProjects as $p)
                    <a href="{{ route('projects.show', $p) }}" class="flex items-center gap-4 px-5 py-3.5 hover:bg-slate-50 transition">
                        <div class="w-10 h-10 rounded-lg bg-brand-50 text-brand-700 grid place-items-center font-bold shrink-0">{{ mb_substr($p->name, 0, 1) }}</div>
                        <div class="min-w-0 flex-1"><div class="font-semibold text-slate-800 truncate">{{ $p->name }}</div><div class="text-xs text-slate-400 truncate">{{ $p->code ? $p->code.' · ' : '' }}{{ $p->client_name ?? __('app.no_client') }}</div></div>
                        <div class="hidden sm:flex items-center gap-2 w-28"><div class="flex-1 h-1.5 rounded-full bg-slate-100 overflow-hidden"><div class="h-full bg-brand-500" style="width: {{ $p->progress }}%"></div></div><span class="text-xs text-slate-500 w-8">{{ $p->progress }}%</span></div>
                        <span class="shrink-0 text-xs px-2.5 py-1 rounded-full bg-{{ $p->statusColor() }}-50 text-{{ $p->statusColor() }}-700">{{ $p->statusLabel() }}</span>
                    </a>
                @empty
                    <div class="px-5 py-12 text-center text-slate-400 text-sm">{{ __('app.no_projects_found') }}</div>
                @endforelse
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h2 class="font-bold text-slate-800 mb-4">{{ __('app.distribution_by_status') }}</h2>
            <div class="space-y-3">
                @foreach (['active','on_hold','completed','cancelled'] as $key)
                    @php $count = $byStatus[$key] ?? 0; $pct = $stats['total'] ? round($count / $stats['total'] * 100) : 0; $color = ['active'=>'emerald','on_hold'=>'amber','completed'=>'sky','cancelled'=>'rose'][$key]; @endphp
                    <div>
                        <div class="flex justify-between text-sm mb-1"><span class="text-slate-600">{{ __('app.status_'.$key) }}</span><span class="text-slate-400">{{ $count }}</span></div>
                        <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden"><div class="h-full bg-{{ $color }}-500" style="width: {{ $pct }}%"></div></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ==================== المشتريات ==================== --}}
<div data-role="procurement" class="role-panel hidden">
    <div class="bg-slate-50 rounded-xl border border-dashed border-slate-300 py-16 text-center">
        <div class="text-4xl mb-3">🛒</div>
        <p class="text-slate-500 font-medium">{{ __('app.procurement') }}</p>
        <p class="text-slate-400 text-sm mt-1">{{ __('app.coming_soon') }}</p>
    </div>
</div>

{{-- ==================== مشاريعي ==================== --}}
<div data-role="mine" class="role-panel hidden">
    @if ($myProjects->count())
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($myProjects as $p)
                <a href="{{ route('projects.show', $p) }}" class="bg-white rounded-xl border border-slate-200 hover:shadow-md p-5 transition">
                    <div class="flex justify-between items-start mb-3">
                        <div><div class="font-bold text-slate-800">{{ $p->name }}</div><div class="text-xs text-slate-400">{{ $p->code ?? '—' }}</div></div>
                        <span class="text-xs px-2.5 py-1 rounded-full bg-{{ $p->statusColor() }}-50 text-{{ $p->statusColor() }}-700 h-fit">{{ $p->statusLabel() }}</span>
                    </div>
                    <div class="flex justify-between text-xs mb-1"><span class="text-slate-400">{{ __('app.progress') }}</span><span class="font-medium text-slate-600">{{ $p->progress }}%</span></div>
                    <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden"><div class="h-full bg-brand-500" style="width: {{ $p->progress }}%"></div></div>
                </a>
            @endforeach
        </div>
    @else
        <div class="bg-white rounded-xl border border-dashed border-slate-300 py-12 text-center text-slate-400 text-sm">{{ __('app.no_my_projects') }}</div>
    @endif
</div>

@push('scripts')
<script>
    function showRole(key) {
        document.querySelectorAll('.role-panel').forEach(p => p.classList.toggle('hidden', p.dataset.role !== key));
        document.querySelectorAll('.role-btn').forEach(b => {
            const on = b.dataset.rtab === key;
            b.classList.toggle('bg-brand-600', on);
            b.classList.toggle('text-white', on);
            b.classList.toggle('font-semibold', on);
            b.classList.toggle('text-slate-500', !on);
        });
    }
</script>
@endpush
@endsection
