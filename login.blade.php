@php $rtl = app()->getLocale() === 'ar'; @endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('app.login') }} · {{ __('app.company') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body{font-family:'Cairo',sans-serif}</style>
</head>
<body class="min-h-screen grid place-items-center bg-gradient-to-br from-brand-900 to-slate-900 p-4">
    <div class="w-full max-w-md">
        <div class="flex justify-center mb-4">
            @php $other = app()->getLocale() === 'ar' ? 'en' : 'ar'; @endphp
            <a href="{{ route('lang.switch', $other) }}" class="text-slate-200/80 hover:text-white text-sm border border-white/20 rounded-lg px-3 py-1.5">
                {{ $other === 'en' ? 'English' : 'العربية' }}
            </a>
        </div>
        <div class="text-center mb-6 text-white">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-brand-600 grid place-items-center font-extrabold text-2xl mb-3">{{ __('app.brand_letter') }}</div>
            <h1 class="text-2xl font-bold">{{ __('app.app_name') }}</h1>
            <p class="text-slate-300/70 text-sm mt-1">{{ __('app.company') }}</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl p-6 sm:p-8">
            <h2 class="text-lg font-bold text-slate-800 mb-1">{{ __('app.login') }}</h2>
            <p class="text-sm text-slate-400 mb-6">{{ __('app.login_subtitle') }}</p>

            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1.5">{{ __('app.email') }}</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus dir="ltr"
                           class="w-full rounded-lg border border-slate-300 focus:border-brand-500 focus:ring-brand-500 text-sm px-3 py-2.5"
                           placeholder="you@rasin.sa">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1.5">{{ __('app.password') }}</label>
                    <input type="password" name="password" required dir="ltr"
                           class="w-full rounded-lg border border-slate-300 focus:border-brand-500 focus:ring-brand-500 text-sm px-3 py-2.5"
                           placeholder="••••••••">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-500">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    {{ __('app.remember_me') }}
                </label>
                <button type="submit" class="w-full rounded-lg bg-brand-600 hover:bg-brand-700 text-white font-semibold py-2.5 transition">
                    {{ __('app.sign_in') }}
                </button>
            </form>
        </div>
        <p class="text-center text-slate-300/40 text-xs mt-6">© {{ date('Y') }} · {{ __('app.all_rights') }}</p>
    </div>
</body>
</html>
