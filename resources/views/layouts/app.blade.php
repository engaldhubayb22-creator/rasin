@php $rtl = app()->getLocale() === 'ar'; @endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('app.dashboard')) · {{ __('app.company') }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: { extend: {
                fontFamily: { sans: ['Cairo', 'sans-serif'] },
                colors: {
                    brand: {50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a'},
                    navy: {700:'#1e293b',800:'#172033',900:'#0f172a'}
                }
            } }
        }
    </script>
    <style>
        body { font-family: 'Cairo', sans-serif; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        #acn-toast{position:fixed;bottom:20px;inset-inline-start:20px;background:#2e7d32;color:#fff;padding:8px 16px;border-radius:6px;font-size:13px;box-shadow:0 4px 12px rgba(0,0,0,.15);opacity:0;transform:translateY(10px);transition:.2s;z-index:60;pointer-events:none}
        #acn-toast.show{opacity:1;transform:none}
        #acn-toast.err{background:#c62828}
        form.acn-ajax.saving{opacity:.55}
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased">
@php
    $r = Route::currentRouteName();
    $hideSide = $rtl ? '-translate-x-full' : 'translate-x-full';
@endphp
<div class="min-h-screen flex">

    {{-- الشريط الجانبي --}}
    <aside id="sidebar" class="fixed lg:sticky top-0 z-40 h-screen w-64 shrink-0 bg-navy-700 text-slate-100
           {{ $hideSide }} lg:translate-x-0 transition-transform duration-200 flex flex-col">
        <div class="h-16 flex items-center gap-3 px-5 border-b border-white/10">
            <div class="w-9 h-9 rounded-lg bg-brand-600 grid place-items-center font-extrabold text-white">{{ __('app.brand_letter') }}</div>
            <div class="leading-tight">
                <div class="font-bold">{{ __('app.brand_letter') === 'R' ? 'Rasin' : 'رسين' }}</div>
                <div class="text-[11px] text-slate-400">{{ __('app.projects') }}</div>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-0.5 text-sm">
            @php
                $link = function ($route, $label, $icon, $active) {
                    $cls = $active ? 'bg-white/15 text-white font-semibold' : 'text-slate-300 hover:bg-white/10';
                    return "<a href=\"$route\" class=\"flex items-center gap-3 px-3 py-2.5 rounded-lg transition $cls\">$icon<span>$label</span></a>";
                };
                $soon = function ($label, $icon) {
                    return "<span class=\"flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 cursor-not-allowed\" title=\"".__('app.coming_soon')."\">$icon<span>$label</span><span class=\"ms-auto text-[9px] bg-white/10 rounded px-1.5 py-0.5\">".__('app.soon')."</span></span>";
                };
                $ic = [
                    'home' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
                    'tasks' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>',
                    'projects' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>',
                    'cart' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
                    'contracts' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
                    'finance' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                    'reports' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
                    'admin' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-6a4 4 0 11-8 0 4 4 0 018 0zm6 3a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
                    'settings' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
                    'clipboard' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>',
                    'check' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-5.5 7.5l1.5 1.5 3-3"/></svg>',
                    'approve' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                    'blueprint' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5a2 2 0 012-2h8l6 6v10a2 2 0 01-2 2H6a2 2 0 01-2-2V5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 3v6h6M8 13h5M8 17h8"/></svg>',
                ];
                $projActive = str_starts_with($r ?? '', 'projects') || str_starts_with($r ?? '', 'tasks') || str_starts_with($r ?? '', 'activities') || str_starts_with($r ?? '', 'budget') || str_starts_with($r ?? '', 'members');
            @endphp

            @php $user = auth()->user(); @endphp
            {!! $link(route('dashboard'), __('app.home'), $ic['home'], $r === 'dashboard') !!}
            {!! $link(route('my-tasks'), __('app.task_center'), $ic['tasks'], $r === 'my-tasks') !!}
            {!! $link(route('requirements.index'), __('app.requirements'), $ic['clipboard'], str_starts_with($r ?? '', 'requirements')) !!}
            {!! $link(route('checklist.center'), __('app.checklist_center'), $ic['check'], str_starts_with($r ?? '', 'checklist')) !!}
            {!! $link(route('projects.index'), __('app.projects'), $ic['projects'], $projActive) !!}
            @if ($user && $user->hasPermission('drawings.view'))
                {!! $link(route('drawings.index'), __('app.drawings'), $ic['blueprint'], str_starts_with($r ?? '', 'drawings')) !!}
            @endif
            @if ($user && $user->hasPermission('procurement.view'))
                {!! $link(route('procurement.index'), __('app.procurement'), $ic['cart'], str_starts_with($r ?? '', 'procurement')) !!}
            @endif
            @if ($user && $user->hasPermission('approvals.view'))
                {!! $link(route('approvals.index'), __('app.approvals'), $ic['approve'], str_starts_with($r ?? '', 'approvals')) !!}
            @endif
            @if ($user && $user->canSeeFinance())
                {!! $link(route('finance'), __('app.finance'), $ic['finance'], $r === 'finance') !!}
            @endif
            @if ($user && $user->canSeeReports())
                {!! $link(route('reports'), __('app.reports'), $ic['reports'], $r === 'reports') !!}
            @endif

            {{-- قسم الإدارة (المدير فقط) --}}
            @if ($user && ($user->isAdmin() || $user->hasPermission('users.manage')))
            <div class="pt-3 mt-2 border-t border-white/10">
                <div class="px-3 pb-1 text-[10px] uppercase tracking-wide text-slate-400/70">{{ __('app.administration') }}</div>
                @if ($user->hasPermission('users.manage'))
                    {!! $link(route('users.index'), __('app.users_roles'), $ic['admin'], str_starts_with($r ?? '', 'users')) !!}
                @endif
                @if ($user->isAdmin())
                    {!! $link(route('admin.checklist-template.index'), __('app.admin_checklist_template'), $ic['check'], str_starts_with($r ?? '', 'admin.checklist')) !!}
                @endif
            </div>
            @endif

            {{-- وحدات مخفية خلف مفاتيح التشغيل (config/features.php) --}}
            @php $anyModule = config('features.contracts') || config('features.administration') || config('features.settings'); @endphp
            @if ($anyModule)
            <div class="pt-3 mt-2 border-t border-white/10">
                <div class="px-3 pb-1 text-[10px] uppercase tracking-wide text-slate-400/70">{{ __('app.modules') }}</div>
                @if (config('features.contracts')) {!! $soon(__('app.contracts'), $ic['contracts']) !!} @endif
                @if (config('features.administration')) {!! $soon(__('app.administration'), $ic['admin']) !!} @endif
                @if (config('features.settings')) {!! $soon(__('app.settings'), $ic['settings']) !!} @endif
            </div>
            @endif
        </nav>

        <div class="p-3 border-t border-white/10 text-[11px] text-slate-400">
            © {{ date('Y') }} {{ __('app.company') }}
        </div>
    </aside>

    <div id="overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/40 z-30 hidden lg:hidden"></div>

    {{-- المحتوى --}}
    <div class="flex-1 min-w-0 flex flex-col">
        <header class="h-16 bg-white border-b border-slate-200 sticky top-0 z-20 flex items-center justify-between px-4 lg:px-6">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="lg:hidden p-2 text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="text-lg font-bold text-slate-800">@yield('page-title', __('app.dashboard'))</h1>
            </div>
            <div class="flex items-center gap-3">
                {{-- مبدّل اللغة --}}
                @php $other = app()->getLocale() === 'ar' ? 'en' : 'ar'; @endphp
                <a href="{{ route('lang.switch', $other) }}"
                   class="flex items-center gap-1.5 text-sm font-semibold text-slate-600 hover:text-brand-700 border border-slate-200 rounded-lg px-2.5 py-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                    {{ $other === 'en' ? 'EN' : 'ع' }}
                </a>
                @auth
                <div class="{{ $rtl ? 'text-left' : 'text-right' }} leading-tight hidden sm:block">
                    <div class="text-sm font-semibold text-slate-700">{{ auth()->user()->name }}</div>
                    <div class="text-[11px] text-slate-400">{{ auth()->user()->roleLabel() }}</div>
                </div>
                <div class="w-9 h-9 rounded-full bg-brand-100 text-brand-700 grid place-items-center font-bold">
                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="p-2 text-slate-400 hover:text-rose-600 transition" title="{{ __('app.logout') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
                @endauth
            </div>
        </header>

        <main class="flex-1 p-4 lg:p-6">
            @if (session('success'))
                <div class="mb-4 flex items-center gap-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('success') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('{{ $hideSide }}');
        document.getElementById('overlay').classList.toggle('hidden');
    }

    /* حفظ فوري (AJAX) لأي نموذج يحمل class="acn-ajax" — بدون مغادرة الصفحة */
    (function () {
        var meta = document.querySelector('meta[name=csrf-token]');
        var SMAP = { not_started:'s-pending', in_progress:'s-in_progress', pending_approval:'s-urgent', completed:'s-completed', not_applicable:'s-cancelled', urgent:'s-urgent', pending:'s-pending', open:'s-pending', done:'s-completed' };
        var SAVED = @json(__('app.saved'));
        var FAILED = @json(__('app.save_failed'));

        function toast(msg, ok) {
            var t = document.getElementById('acn-toast');
            if (!t) { t = document.createElement('div'); t.id = 'acn-toast'; document.body.appendChild(t); }
            t.textContent = msg;
            t.className = 'show' + (ok ? '' : ' err');
            clearTimeout(t._h);
            t._h = setTimeout(function () { t.className = ''; }, 1600);
        }
        window.acnToast = toast;

        document.addEventListener('submit', function (e) {
            var f = e.target;
            if (!f.classList || !f.classList.contains('acn-ajax')) return;
            e.preventDefault();
            f.classList.add('saving');
            fetch(f.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': meta ? meta.content : ''
                },
                body: new FormData(f)
            })
            .then(function (r) { if (!r.ok) throw 0; return r.json(); })
            .then(function (d) {
                var sel = f.querySelector('select[name=status]');
                if (sel) {
                    var cls = (d && d.status_class) || SMAP[sel.value] || 's-pending';
                    sel.className = sel.className.replace(/\bs-[a-z_]+\b/g, '').replace(/\s+/g, ' ').trim();
                    sel.classList.add(cls);
                }
                var act = f.querySelector('input[name=actual_date]');
                if (act && d && d.actual_date && !act.value) act.value = d.actual_date;
                toast(SAVED, true);
            })
            .catch(function () { toast(FAILED, false); })
            .finally(function () { f.classList.remove('saving'); });
        });
    })();
</script>
@stack('scripts')
</body>
</html>
