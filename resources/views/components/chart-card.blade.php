@props(['title', 'series', 'type' => 'line', 'color' => 'brand', 'currency' => '', 'empty' => 'No activity in this period yet.'])

@php
    $hasData = collect($series['values'] ?? [])->contains(fn ($v) => (float) $v != 0.0);
@endphp

<div {{ $attributes->merge(['class' => 'card p-5']) }}>
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold text-ink-800">{{ $title }}</h3>
        {{ $actions ?? '' }}
    </div>
    <div class="relative mt-4 h-52">
        @if ($hasData)
            <canvas x-data="chart(@js(['type' => $type, 'labels' => $series['labels'], 'values' => $series['values'], 'color' => $color, 'currency' => $currency]))"></canvas>
        @else
            <div class="flex h-full flex-col items-center justify-center rounded-xl border border-dashed border-ink-200 text-center">
                <x-icon name="chart" class="size-6 text-ink-300" />
                <p class="mt-2 text-sm text-ink-400">{{ $empty }}</p>
            </div>
        @endif
    </div>
</div>
