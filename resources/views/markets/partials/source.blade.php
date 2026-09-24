@if (! $price)
    <span class="text-xs text-ink-400">{{ __('Awaiting price') }}</span>
@elseif ($price->is_simulated)
    <span class="text-[10px] font-semibold tracking-wide text-amber-600 uppercase">{{ __('Simulated') }}</span>
@elseif ($price->isStale())
    <span class="text-xs font-medium text-rose-600" title="{{ $price->recorded_at->toDayDateTimeString() }}">{{ __('Delayed') }} · {{ $price->recorded_at->diffForHumans() }}</span>
@else
    <span class="text-xs text-emerald-700" title="{{ $price->recorded_at->toDayDateTimeString() }}">{{ __('Live') }} · {{ ucfirst($price->source) }}</span>
@endif
