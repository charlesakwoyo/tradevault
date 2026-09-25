@php
    use App\Support\Money;

    $changeText = fn (?float $pct) => $pct === null ? '—' : ($pct > 0 ? '+' : '').number_format($pct, 2).'%';
    $changeClass = fn (?float $pct) => $pct === null ? 'text-ink-400' : ($pct > 0 ? 'text-emerald-600' : ($pct < 0 ? 'text-rose-600' : 'text-ink-500'));
    $limits = config('tradevault.limits');
    $currency = config('tradevault.base_currency');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — {{ __('Live crypto markets, built on clarity') }}</title>
    <meta name="description" content="{{ __('Follow live crypto prices, open a verified account in minutes and see every balance backed by a full ledger.') }}">
    @include('partials.favicons')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans text-ink-900 antialiased">

    {{-- Header --}}
    <header class="sticky top-0 z-40 border-b border-ink-100 bg-white/90 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
            <a href="{{ route('home') }}"><x-logo /></a>
            <nav class="hidden items-center gap-6 text-sm font-medium text-ink-600 md:flex" aria-label="{{ __('Sections') }}">
                <a href="#markets" class="hover:text-ink-900">{{ __('Markets') }}</a>
                <a href="#how-it-works" class="hover:text-ink-900">{{ __('How it works') }}</a>
                <a href="#security" class="hover:text-ink-900">{{ __('Security') }}</a>
                <a href="#faq" class="hover:text-ink-900">{{ __('FAQ') }}</a>
            </nav>
            <div class="flex items-center gap-3 text-sm">
                @auth
                    <a href="{{ auth()->user()->isStaff() && ! auth()->user()->isCustomer() ? route('admin.dashboard') : route('dashboard') }}" class="btn-primary">{{ __('Open dashboard') }}</a>
                @else
                    <a href="{{ route('login') }}" class="font-medium text-ink-600 hover:text-ink-900">{{ __('Sign in') }}</a>
                    <a href="{{ route('register') }}" class="btn-primary hidden min-[400px]:inline-flex">{{ __('Create account') }}</a>
                @endauth
            </div>
        </div>
    </header>

    <main>
        {{-- Hero + live board --}}
        <section class="mx-auto grid max-w-6xl items-center gap-12 px-4 pt-14 pb-20 sm:px-6 lg:grid-cols-2 lg:pt-20">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full border border-brand-100 bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">
                    <span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-brand-400 opacity-75"></span><span class="relative inline-flex size-2 rounded-full bg-brand-600"></span></span>
                    {{ __('Live crypto prices, updated every minute') }}
                </p>
                <h1 class="mt-5 text-4xl font-semibold tracking-tight sm:text-5xl">{{ __('Follow the markets. Know exactly where your money stands.') }}</h1>
                <p class="mt-6 text-lg leading-relaxed text-ink-500">
                    {{ __(':app gives you real-time prices on :count leading crypto-assets and an account where every balance is backed by a full double-entry ledger — nothing hidden, nothing estimated.', ['app' => config('app.name'), 'count' => $markets->count()]) }}
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="btn-primary px-6 py-3">{{ __('Create free account') }}</a>
                    <a href="#markets" class="btn-secondary px-6 py-3">{{ __('See live prices') }}</a>
                </div>
                <p class="mt-4 text-xs text-ink-400">{{ __('Sign up with email or Google. You must be :age or older.', ['age' => config('tradevault.registration.minimum_age')]) }}</p>
            </div>

            <div class="card overflow-hidden">
                <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4">
                    <h2 class="font-semibold">{{ __('Market snapshot') }}</h2>
                    <span class="text-xs text-ink-400">
                        {{ $lastUpdated ? __('Updated :time', ['time' => $lastUpdated->diffForHumans()]) : __('Waiting for prices') }}
                    </span>
                </div>
                <ul class="divide-y divide-ink-100">
                    @forelse ($markets->take(6) as $market)
                        @php $price = $market->latestPrice; $pct = $price?->changePercent(); @endphp
                        <li class="flex items-center justify-between gap-4 px-5 py-3">
                            <div class="flex items-center gap-3">
                                <span class="flex size-9 items-center justify-center rounded-full bg-brand-50 text-xs font-bold text-brand-700">{{ mb_substr($market->base_asset, 0, 3) }}</span>
                                <div>
                                    <p class="text-sm font-semibold">{{ $market->base_asset }}</p>
                                    @if ($market->name !== $market->base_asset)<p class="text-xs text-ink-400">{{ $market->name }}</p>@endif
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold tabular-nums">{{ $price ? Money::format($price->last, $market->quote_currency, $market->price_precision) : '—' }}</p>
                                <p class="text-xs font-medium tabular-nums {{ $changeClass($pct) }}">{{ $changeText($pct) }} <span class="font-normal text-ink-400">24h</span></p>
                            </div>
                        </li>
                    @empty
                        <li class="px-5 py-10 text-center text-sm text-ink-400">{{ __('Markets will appear here shortly.') }}</li>
                    @endforelse
                </ul>
                <a href="#markets" class="block border-t border-ink-100 px-5 py-3 text-center text-sm font-medium text-brand-700 hover:bg-brand-50">{{ __('View all :count markets', ['count' => $markets->count()]) }}</a>
            </div>
        </section>

        {{-- Facts strip (all values come from configuration or data, nothing invented) --}}
        <section class="border-y border-ink-100 bg-ink-50/50">
            <dl class="mx-auto grid max-w-6xl grid-cols-2 gap-6 px-4 py-10 sm:px-6 lg:grid-cols-4">
                @foreach ([
                    [$markets->count(), __('Crypto markets listed')],
                    [__('60 sec'), __('Price refresh interval')],
                    [__('2FA'), __('Two-factor sign-in available')],
                    [__('100%'), __('Balances backed by ledger entries')],
                ] as [$value, $label])
                    <div>
                        <dt class="text-sm text-ink-500">{{ $label }}</dt>
                        <dd class="mt-1 text-2xl font-semibold tracking-tight text-ink-900">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- Features --}}
        <section class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold tracking-wider text-brand-600 uppercase">{{ __('Why :app', ['app' => config('app.name')]) }}</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight">{{ __('Built for people who want to see the whole picture') }}</h2>
            </div>
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['chart', __('Live market data'), __('Prices, 24-hour change, highs, lows and volume, refreshed every minute. If a feed is delayed you are told — stale prices are never shown as live.')],
                    ['list', __('A ledger you can inspect'), __('Every credit and debit is a balanced double-entry record. Your transaction history shows exactly what moved, when and why.')],
                    ['shield', __('Account protection'), __('Strong password rules, optional two-factor authentication with recovery codes, and a record of every sign-in.')],
                    ['badge', __('Verified identities'), __('Email, phone and identity verification keep the platform safe and meet know-your-customer requirements.')],
                    ['percent', __('Limits shown up front'), __('Minimums, maximums and daily limits are displayed before you submit a request — no surprises after the fact.')],
                    ['lifebuoy', __('Real support'), __('Questions about your account? Reach our team at :email.', ['email' => config('tradevault.support_email')])],
                ] as [$icon, $heading, $text])
                    <div class="rounded-2xl border border-ink-100 bg-white p-6 transition hover:border-brand-200 hover:shadow-sm">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-brand-50"><x-icon :name="$icon" class="size-5 text-brand-600" /></span>
                        <h3 class="mt-4 font-semibold">{{ $heading }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-500">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- All markets --}}
        <section id="markets" class="scroll-mt-20 border-t border-ink-100 bg-ink-50/50">
            <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold tracking-wider text-brand-600 uppercase">{{ __('Markets') }}</p>
                        <h2 class="mt-3 text-3xl font-semibold tracking-tight">{{ __('Live crypto prices') }}</h2>
                    </div>
                    <p class="text-sm text-ink-500">{{ __('USD prices from USDT pairs. Indicative only.') }}</p>
                </div>

                <div class="card mt-8 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead class="bg-ink-50/60">
                                <tr>
                                    <th>{{ __('Asset') }}</th>
                                    <th class="text-right">{{ __('Price') }}</th>
                                    <th class="text-right">{{ __('24h change') }}</th>
                                    <th class="hidden text-right sm:table-cell">{{ __('24h high') }}</th>
                                    <th class="hidden text-right sm:table-cell">{{ __('24h low') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-ink-100">
                                @forelse ($markets as $market)
                                    @php $price = $market->latestPrice; $pct = $price?->changePercent(); @endphp
                                    <tr>
                                        <td><span class="font-semibold text-ink-900">{{ $market->base_asset }}</span> @if ($market->name !== $market->base_asset)<span class="ml-1 text-ink-400">{{ $market->name }}</span>@endif</td>
                                        <td class="text-right font-semibold tabular-nums">{{ $price ? Money::format($price->last, $market->quote_currency, $market->price_precision) : '—' }}</td>
                                        <td class="text-right font-medium tabular-nums {{ $changeClass($pct) }}">{{ $changeText($pct) }}</td>
                                        <td class="hidden text-right tabular-nums text-ink-500 sm:table-cell">{{ $price?->high ? Money::format($price->high, '', $market->price_precision) : '—' }}</td>
                                        <td class="hidden text-right tabular-nums text-ink-500 sm:table-cell">{{ $price?->low ? Money::format($price->low, '', $market->price_precision) : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-12 text-center text-ink-400">{{ __('Markets will appear here shortly.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        {{-- How it works --}}
        <section id="how-it-works" class="mx-auto max-w-6xl scroll-mt-20 px-4 py-20 sm:px-6">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold tracking-wider text-brand-600 uppercase">{{ __('How it works') }}</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight">{{ __('From sign-up to live markets in minutes') }}</h2>
            </div>
            <ol class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    [__('Create your account'), __('Sign up with your email or continue with Google. It takes about two minutes.'), false],
                    [__('Verify yourself'), __('Confirm your email and phone, then complete identity verification (KYC).'), false],
                    [__('Follow the markets'), __('Track live prices and 24-hour moves across every listed crypto-asset.'), false],
                    [__('Fund and trade'), __('Deposits, withdrawals and trading are being prepared and will open to verified customers.'), true],
                ] as $i => [$heading, $text, $soon])
                    <li class="relative rounded-2xl border border-ink-100 bg-white p-6">
                        <div class="flex items-center justify-between gap-2">
                            <span class="flex size-9 items-center justify-center rounded-full bg-brand-600 text-sm font-semibold text-white">{{ $i + 1 }}</span>
                            @if ($soon)
                                <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[10px] font-semibold tracking-wide text-brand-700 uppercase">{{ __('Coming soon') }}</span>
                            @endif
                        </div>
                        <h3 class="mt-4 font-semibold">{{ $heading }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-500">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- Security --}}
        <section id="security" class="scroll-mt-20 border-t border-ink-100 bg-ink-50/50">
            <div class="mx-auto grid max-w-6xl gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2">
                <div>
                    <p class="text-sm font-semibold tracking-wider text-brand-600 uppercase">{{ __('Security') }}</p>
                    <h2 class="mt-3 text-3xl font-semibold tracking-tight">{{ __('Your account, protected at every step') }}</h2>
                    <p class="mt-4 leading-relaxed text-ink-500">{{ __('Security is built into the platform, not bolted on. Here is what protects your account today.') }}</p>
                </div>
                <ul class="space-y-4">
                    @foreach ([
                        ['lock', __('Two-factor authentication'), __('Add an authenticator app so a password alone is never enough. Staff accounts are required to use it.')],
                        ['mail', __('Verified email and phone'), __('Contact details are confirmed before sensitive actions, so account messages reach you.')],
                        ['eye', __('Full audit trail'), __('Sign-ins, security changes and administrative actions are recorded in an append-only log.')],
                        ['alert', __('Automatic protection'), __('Repeated failed sign-ins are rate-limited, and suspended accounts cannot sign in.')],
                    ] as [$icon, $heading, $text])
                        <li class="flex gap-4 rounded-2xl border border-ink-100 bg-white p-5">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50"><x-icon :name="$icon" class="size-5 text-brand-600" /></span>
                            <div>
                                <h3 class="font-semibold">{{ $heading }}</h3>
                                <p class="mt-1 text-sm leading-relaxed text-ink-500">{{ $text }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- FAQ --}}
        <section id="faq" class="mx-auto max-w-3xl scroll-mt-20 px-4 py-20 sm:px-6">
            <p class="text-center text-sm font-semibold tracking-wider text-brand-600 uppercase">{{ __('FAQ') }}</p>
            <h2 class="mt-3 text-center text-3xl font-semibold tracking-tight">{{ __('Questions, answered') }}</h2>
            <div class="mt-10 divide-y divide-ink-100 rounded-2xl border border-ink-100">
                @foreach ([
                    [__('Which markets can I follow?'), __('Currently :list — all quoted in US dollars.', ['list' => $markets->pluck('base_asset')->implode(', ') ?: __('a range of leading crypto-assets')])],
                    [__('Where do the prices come from?'), __('Prices come from Binance’s public market data and are refreshed every minute. USD prices are taken from USDT pairs, a dollar-pegged stablecoin, so they are indicative.')],
                    [__('Who can open an account?'), __('Anyone aged :age or over living in a supported country. You will verify your email, phone number and identity.', ['age' => config('tradevault.registration.minimum_age')])],
                    [__('Can I sign up with Google?'), __('Yes. Choose “Continue with Google” and finish a short form with the details the platform requires by law.')],
                    [__('Can I deposit and trade yet?'), __('Not yet. Deposits, withdrawals and trading are being prepared and will be announced to registered customers first.')],
                    [__('What limits will apply?'), __('Deposits from :dmin to :dmax per transaction; withdrawals up to :wday per day. Limits are always shown before you submit.', ['dmin' => Money::format($limits['deposit_min'], $currency), 'dmax' => Money::format($limits['deposit_max'], $currency), 'wday' => Money::format($limits['withdrawal_daily_max'], $currency)])],
                    [__('Is trading risky?'), __('Yes. Crypto-asset prices are highly volatile and you can lose some or all of the money you invest. Please read the Risk Disclosure before investing.')],
                ] as [$question, $answer])
                    <details class="group px-5 py-4 [&_summary::-webkit-details-marker]:hidden">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-medium text-ink-900">
                            {{ $question }}
                            <span class="text-xl leading-none text-brand-600 transition group-open:rotate-45" aria-hidden="true">+</span>
                        </summary>
                        <p class="mt-3 text-sm leading-relaxed text-ink-500">{{ $answer }}</p>
                    </details>
                @endforeach
            </div>
        </section>

        {{-- Closing call to action --}}
        <section class="mx-auto max-w-6xl px-4 pb-20 sm:px-6">
            <div class="flex flex-col items-start justify-between gap-6 rounded-3xl border border-brand-100 bg-brand-50 p-8 sm:flex-row sm:items-center sm:p-10">
                <div>
                    <h2 class="text-2xl font-semibold tracking-tight">{{ __('Ready to get started?') }}</h2>
                    <p class="mt-2 text-ink-500">{{ __('Create your account today and follow the markets live.') }}</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="btn-primary px-6 py-3">{{ __('Create free account') }}</a>
                    <a href="{{ route('legal.show', 'risk') }}" class="btn-secondary px-6 py-3">{{ __('Understand the risks') }}</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-ink-100">
        <div class="mx-auto max-w-6xl space-y-4 px-4 py-8 text-xs leading-relaxed text-ink-400 sm:px-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <x-logo />
                <a href="mailto:{{ config('tradevault.support_email') }}" class="hover:text-ink-700">{{ config('tradevault.support_email') }}</a>
            </div>
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
