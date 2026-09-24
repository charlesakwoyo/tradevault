@props(['title' => null, 'wide' => false])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @include('partials.favicons')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-ink-900 antialiased">

    <div class="flex min-h-full flex-col items-center justify-center px-4 py-10 sm:px-6">
        <a href="{{ route('home') }}" class="mb-8"><x-logo /></a>

        <div class="card w-full {{ $wide ? 'max-w-2xl' : 'max-w-md' }} p-6 sm:p-8">
            @if ($title)
                <h1 class="text-xl font-semibold text-ink-900">{{ $title }}</h1>
            @endif
            @isset($subtitle)
                <p class="mt-1 text-sm text-ink-500">{{ $subtitle }}</p>
            @endisset

            <div class="mt-6">
                <x-flash />
                {{ $slot }}
            </div>
        </div>

        <p class="mt-8 max-w-md text-center text-xs leading-relaxed text-ink-400">
            {{ __('Trading and investing involve risk, including the possible loss of capital. Past performance does not guarantee future results.') }}
            <a href="{{ route('legal.show', 'risk') }}" class="underline hover:text-ink-600">{{ __('Risk disclosure') }}</a>
        </p>
    </div>
</body>
</html>
