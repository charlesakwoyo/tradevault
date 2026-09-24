@props(['dark' => false])

{{-- Mark: three rising bars in a blue tile (same artwork as public/favicon.svg). `dark` = drawn on a blue background. --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <svg viewBox="0 0 32 32" class="size-8" aria-hidden="true">
        <rect width="32" height="32" rx="8" class="{{ $dark ? 'fill-white' : 'fill-brand-600' }}" />
        <g class="{{ $dark ? 'fill-brand-600' : 'fill-white' }}">
            <rect x="7" y="17" width="4.5" height="8" rx="1.25" fill-opacity=".55" />
            <rect x="13.75" y="12" width="4.5" height="13" rx="1.25" fill-opacity=".8" />
            <rect x="20.5" y="7" width="4.5" height="18" rx="1.25" />
        </g>
    </svg>
    <span class="text-lg font-semibold tracking-tight {{ $dark ? 'text-white' : 'text-ink-900' }}">Trade<span class="{{ $dark ? 'text-brand-200' : 'text-brand-600' }}">Vault</span></span>
</span>
