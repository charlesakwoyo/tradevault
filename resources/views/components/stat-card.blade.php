@props(['label', 'value', 'icon' => null, 'hint' => null, 'tone' => 'neutral'])

@php
    $valueTone = match ($tone) {
        'positive' => 'text-emerald-600',
        'negative' => 'text-rose-600',
        default => 'text-ink-900',
    };
@endphp

<div {{ $attributes->merge(['class' => 'card p-5']) }}>
    <div class="flex items-center justify-between gap-3">
        <p class="text-sm font-medium text-ink-500">{{ $label }}</p>
        @if ($icon)
            <span class="rounded-lg bg-brand-50 p-2 text-brand-700"><x-icon :name="$icon" class="size-4" /></span>
        @endif
    </div>
    <p class="mt-3 text-2xl font-semibold tracking-tight tabular-nums {{ $valueTone }}">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-ink-400">{{ $hint }}</p>
    @endif
</div>
