@props(['status'])

@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $label = method_exists($status, 'label') ? $status->label() : \Illuminate\Support\Str::headline($value);

    $tone = match ($value) {
        'approved', 'completed', 'filled', 'active', 'resolved' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'pending', 'processing', 'open', 'partially_filled', 'in_progress', 'under_review' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'rejected', 'failed', 'suspended', 'cancelled', 'reversed' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
        'requires_more_information', 'waiting_for_user' => 'bg-sky-50 text-sky-700 ring-sky-600/20',
        default => 'bg-ink-50 text-ink-600 ring-ink-500/20',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {$tone}"]) }}>
    {{ $slot->isEmpty() ? $label : $slot }}
</span>
