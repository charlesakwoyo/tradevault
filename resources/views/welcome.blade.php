<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — {{ __('Trade and invest with clarity') }}</title>
    @include('partials.favicons')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans text-ink-900 antialiased">

    <header class="mx-auto flex max-w-6xl items-center justify-between px-4 py-5 sm:px-6">
        <x-logo />
        <nav class="flex items-center gap-3 text-sm">
            @auth
                <a href="{{ auth()->user()->isStaff() && ! auth()->user()->isCustomer() ? route('admin.dashboard') : route('dashboard') }}" class="btn-primary">{{ __('Open dashboard') }}</a>
            @else
                <a href="{{ route('login') }}" class="font-medium text-ink-600 hover:text-ink-900">{{ __('Sign in') }}</a>
                <a href="{{ route('register') }}" class="btn-primary">{{ __('Create account') }}</a>
            @endauth
        </nav>
    </header>

    <main class="mx-auto max-w-6xl px-4 pt-16 pb-24 sm:px-6 lg:pt-24">
        <div class="max-w-2xl">
            <p class="text-sm font-semibold tracking-wider text-brand-600 uppercase">{{ __('Forex · Crypto · Stocks · Commodities') }}</p>
            <h1 class="mt-4 text-4xl font-semibold tracking-tight sm:text-5xl">{{ __('Trade and invest with clarity.') }}</h1>
            <p class="mt-6 text-lg leading-relaxed text-ink-500">
                {{ __('A transparent platform where every balance comes from a full ledger, every fee is shown before you confirm, and every withdrawal status is visible to you.') }}
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('register') }}" class="btn-primary px-6 py-3">{{ __('Get started') }}</a>
                <a href="{{ route('legal.show', 'risk') }}" class="btn-secondary px-6 py-3">{{ __('Understand the risks') }}</a>
            </div>
        </div>

        <div class="mt-20 grid gap-6 sm:grid-cols-3">
            @foreach ([
                ['wallet', __('Double-entry ledger'), __('Every deposit, trade, fee and withdrawal is a balanced ledger entry you can inspect.')],
                ['shield', __('Security first'), __('Two-factor authentication, verified contact details and a full audit trail of account activity.')],
                ['badge', __('Clear rules'), __('Limits, fees and verification requirements are shown up front, before you submit anything.')],
            ] as [$icon, $heading, $text])
                <div class="rounded-2xl border border-ink-100 bg-white p-6">
                    <x-icon :name="$icon" class="size-6 text-brand-600" />
                    <h2 class="mt-4 font-semibold">{{ $heading }}</h2>
                    <p class="mt-2 text-sm leading-relaxed text-ink-500">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </main>

    <footer class="border-t border-ink-100">
        <div class="mx-auto max-w-6xl space-y-4 px-4 py-8 text-xs leading-relaxed text-ink-400 sm:px-6">
            <p><strong class="text-ink-700">{{ __('Risk warning:') }}</strong> {{ __('Trading and investing involve significant risk, including the possible loss of all capital invested. No returns are guaranteed. Past performance does not indicate future results.') }}</p>
            <div class="flex flex-wrap gap-x-4 gap-y-2">
                @foreach (config('tradevault.legal.documents') as $key => $label)
                    <a href="{{ route('legal.show', $key) }}" class="hover:text-ink-700">{{ __($label) }}</a>
                @endforeach
            </div>
            <p>&copy; {{ now()->year }} {{ config('app.name') }}</p>
        </div>
    </footer>
</body>
</html>
