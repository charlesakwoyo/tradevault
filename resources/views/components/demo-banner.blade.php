@if (config('tradevault.demo_mode'))
    <div class="sticky top-0 z-50 h-7 truncate bg-amber-400 px-4 text-center text-xs leading-7 font-semibold tracking-wide text-amber-950"
        title="{{ __('Market prices are simulated and balances are not real money.') }}">
        {{ __('DEMO / SANDBOX — simulated prices, not real money') }}
    </div>
@endif
