@props(['dark' => false])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <svg viewBox="0 0 32 32" class="size-8" aria-hidden="true">
        <rect width="32" height="32" rx="9" class="fill-brand-500" />
        <path d="M9 21.5 14 15l3.5 3.5L23 11" fill="none" stroke="white" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
        <circle cx="23" cy="11" r="1.8" fill="white" />
    </svg>
    <span class="text-lg font-semibold tracking-tight {{ $dark ? 'text-white' : 'text-ink-900' }}">Trade<span class="text-brand-500">Vault</span></span>
</span>
