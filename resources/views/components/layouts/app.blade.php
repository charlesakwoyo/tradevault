@props(['title' => null, 'area' => 'customer'])

@php
    $user = auth()->user();
    $isAdmin = $area === 'admin';
    $items = $isAdmin ? \App\Support\Navigation::admin($user) : \App\Support\Navigation::customer();
    $mobileItems = $isAdmin ? [] : \App\Support\Navigation::customerMobile();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}{{ $isAdmin ? ' Admin' : '' }}</title>
    @include('partials.favicons')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-ink-900 antialiased">

    <div x-data="{ sidebarOpen: false }" class="min-h-full">
        {{-- Mobile drawer --}}
        <div x-cloak x-show="sidebarOpen" class="relative z-50 lg:hidden" role="dialog" aria-modal="true">
            <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 bg-ink-950/60" @click="sidebarOpen = false"></div>
            <div x-show="sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                class="fixed inset-y-0 left-0 flex w-72 flex-col bg-white">
                <div class="flex h-16 items-center justify-between px-5">
                    <x-logo />
                    <button type="button" @click="sidebarOpen = false" class="text-ink-400 hover:text-ink-700" aria-label="{{ __('Close menu') }}"><x-icon name="x" /></button>
                </div>
                @include('components.layouts.partials.nav', ['items' => $items, 'isAdmin' => $isAdmin])
            </div>
        </div>

        {{-- Desktop sidebar --}}
        <aside class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col">
            <div class="flex grow flex-col overflow-y-auto border-r border-ink-100 bg-white">
                <div class="flex h-16 shrink-0 items-center px-5">
                    <a href="{{ $isAdmin ? route('admin.dashboard') : route('dashboard') }}"><x-logo /></a>
                    @if ($isAdmin)
                        <span class="ml-2 rounded bg-brand-50 px-1.5 py-0.5 text-[10px] font-semibold tracking-wider text-brand-700 uppercase">{{ __('Admin') }}</span>
                    @endif
                </div>
                @include('components.layouts.partials.nav', ['items' => $items, 'isAdmin' => $isAdmin])
            </div>
        </aside>

        <div class="lg:pl-64">
            {{-- Top bar --}}
            <header class="sticky top-0 z-40 flex h-16 items-center gap-4 border-b border-ink-100 bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-8">
                <button type="button" @click="sidebarOpen = true" class="-m-2 p-2 text-ink-600 lg:hidden" aria-label="{{ __('Open menu') }}"><x-icon name="menu" /></button>
                <div class="min-w-0 flex-1">
                    @if ($title)
                        <h1 class="truncate text-lg font-semibold text-ink-900">{{ $title }}</h1>
                    @endif
                </div>

                @if ($user->isStaff() && $user->isCustomer())
                    <a href="{{ $isAdmin ? route('dashboard') : route('admin.dashboard') }}" class="hidden text-sm font-medium text-brand-700 hover:text-brand-800 sm:inline">
                        {{ $isAdmin ? __('Customer view') : __('Back office') }}
                    </a>
                @endif

                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open = !open" @keydown.escape.window="open = false" class="flex items-center gap-2 rounded-full p-1 pr-2 hover:bg-ink-50" aria-haspopup="true" :aria-expanded="open">
                        <span class="flex size-8 items-center justify-center rounded-full bg-brand-600 text-xs font-semibold text-white">{{ \Illuminate\Support\Str::of($user->name)->explode(' ')->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}</span>
                        <span class="hidden text-sm font-medium text-ink-700 sm:block">{{ $user->name }}</span>
                    </button>
                    <div x-cloak x-show="open" x-transition @click.outside="open = false" class="absolute right-0 mt-2 w-56 rounded-xl border border-ink-100 bg-white py-1 shadow-lg">
                        <div class="border-b border-ink-100 px-4 py-2.5">
                            <p class="truncate text-sm font-medium text-ink-900">{{ $user->name }}</p>
                            <p class="truncate text-xs text-ink-400">{{ $user->email }}</p>
                        </div>
                        <a href="{{ route('account.profile') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-ink-700 hover:bg-ink-50"><x-icon name="user" class="size-4" /> {{ __('Profile') }}</a>
                        <a href="{{ route('account.security') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-ink-700 hover:bg-ink-50"><x-icon name="shield" class="size-4" /> {{ __('Security') }}</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-ink-700 hover:bg-ink-50"><x-icon name="logout" class="size-4" /> {{ __('Log out') }}</button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="px-4 py-6 pb-24 sm:px-6 lg:px-8 lg:pb-10">
                <x-flash />
                {{ $slot }}
            </main>
        </div>

        {{-- Mobile bottom navigation (customer area) --}}
        @if ($mobileItems)
            <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-ink-100 bg-white lg:hidden" aria-label="{{ __('Primary') }}">
                <div class="grid" style="grid-template-columns: repeat({{ count($mobileItems) }}, minmax(0, 1fr))">
                    @foreach ($mobileItems as $item)
                        @php $active = request()->routeIs($item['active']); @endphp
                        <a href="{{ route($item['route']) }}" class="flex flex-col items-center gap-1 py-2.5 text-[11px] font-medium {{ $active ? 'text-brand-600' : 'text-ink-400' }}" @if ($active) aria-current="page" @endif>
                            <x-icon :name="$item['icon']" />
                            {{ __($item['label']) }}
                        </a>
                    @endforeach
                </div>
            </nav>
        @endif
    </div>

    @if (\App\Services\Assistant\TradeVaultAssistant::isConfigured())
        <x-assistant-widget />
    @endif
</body>
</html>
