@php
    use App\Enums\KycStatus;
    use App\Support\Money;

    $c = $summary['currency'];
    $fmt = fn ($v) => Money::format($v, $c);
    $pnlTone = fn ($v) => Money::isPositive($v) ? 'positive' : (Money::isNegative($v) ? 'negative' : 'neutral');
    $user = auth()->user();
@endphp

<x-layouts.app :title="__('Dashboard')">
    {{-- Verification checklist --}}
    @if (! $user->hasVerifiedPhone() || $summary['kyc_status'] !== KycStatus::Approved)
        <div class="card mb-6 flex flex-col gap-4 border-brand-100 bg-brand-50/50 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-ink-900">{{ __('Finish setting up your account') }}</h2>
                <p class="mt-1 text-sm text-ink-500">{{ __('Verified accounts can deposit, trade and withdraw without interruption.') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 ring-1 ring-ink-100">
                    <x-icon name="{{ $user->hasVerifiedEmail() ? 'check' : 'mail' }}" class="size-4 {{ $user->hasVerifiedEmail() ? 'text-emerald-600' : 'text-ink-400' }}" /> {{ __('Email') }}
                </span>
                <a href="{{ route('account.profile') }}" class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 ring-1 ring-ink-100 hover:ring-brand-300">
                    <x-icon name="{{ $user->hasVerifiedPhone() ? 'check' : 'phone' }}" class="size-4 {{ $user->hasVerifiedPhone() ? 'text-emerald-600' : 'text-ink-400' }}" /> {{ __('Phone') }}
                </a>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 ring-1 ring-ink-100">
                    <x-icon name="badge" class="size-4 text-ink-400" /> {{ __('Identity') }}: <x-status-badge :status="$summary['kyc_status']" />
                </span>
            </div>
        </div>
    @endif

    {{-- Headline figures --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card :label="__('Available balance')" :value="$fmt($summary['available'])" icon="wallet"
            :hint="Money::isPositive($summary['reserved']) ? __(':amount reserved for pending requests', ['amount' => $fmt($summary['reserved'])]) : __('Ready to trade or withdraw')" />
        <x-stat-card :label="__('Portfolio value')" :value="$fmt($summary['portfolio_value'])" icon="pie"
            :hint="__('Cash plus open positions at latest price')" />
        <x-stat-card :label="__('Total deposited')" :value="$fmt($summary['total_deposited'])" icon="download" />
        <x-stat-card :label="__('Total withdrawn')" :value="$fmt($summary['total_withdrawn'])" icon="upload" />
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card :label="__('Open positions')" :value="$summary['open_positions']" icon="arrows" />
        <x-stat-card :label="__('Realized P/L')" :value="$fmt($summary['realized_pnl'])" :tone="$pnlTone($summary['realized_pnl'])"
            :hint="__('From closed trades')" />
        <x-stat-card :label="__('Unrealized P/L')" :value="$fmt($summary['unrealized_pnl'])" :tone="$pnlTone($summary['unrealized_pnl'])"
            :hint="$summary['uses_simulated_prices'] ? __('Valued at simulated sandbox prices') : __('Valued at latest market prices')" />
    </div>

    {{-- Charts (real data only; empty state until activity exists) --}}
    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <x-chart-card :title="__('Portfolio performance (90 days)')" :series="$charts['portfolio']" :currency="$c"
            :empty="__('Daily portfolio snapshots will appear here once you hold assets.')" />
        <x-chart-card :title="__('Trading performance — realized P/L (30 days)')" :series="$charts['trading']" type="bar" :currency="$c"
            :empty="__('No closed trades in the last 30 days.')" />
        <x-chart-card :title="__('Deposits (30 days)')" :series="$charts['deposits']" type="bar" :currency="$c"
            :empty="__('No deposits in the last 30 days.')" />
        <x-chart-card :title="__('Withdrawals (30 days)')" :series="$charts['withdrawals']" type="bar" color="ink" :currency="$c"
            :empty="__('No withdrawals in the last 30 days.')" />
    </div>

    {{-- Positions --}}
    @if ($summary['positions'])
        <div class="card mt-6 overflow-hidden">
            <div class="border-b border-ink-100 px-5 py-4"><h2 class="text-sm font-semibold text-ink-800">{{ __('Open positions') }}</h2></div>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead class="bg-ink-50/60"><tr><th>{{ __('Asset') }}</th><th class="text-right">{{ __('Quantity') }}</th><th class="text-right">{{ __('Avg. entry') }}</th><th class="text-right">{{ __('Price') }}</th><th class="text-right">{{ __('Value') }}</th><th class="text-right">{{ __('Unrealized P/L') }}</th></tr></thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($summary['positions'] as $p)
                            <tr>
                                <td class="font-medium text-ink-900">{{ $p['symbol'] }} @if ($p['is_simulated'])<span class="ml-1 text-[10px] font-semibold text-amber-600 uppercase">{{ __('sim') }}</span>@endif</td>
                                <td class="text-right tabular-nums">{{ rtrim(rtrim($p['quantity'], '0'), '.') }}</td>
                                <td class="text-right tabular-nums">{{ Money::format($p['average_entry_price']) }}</td>
                                <td class="text-right tabular-nums">{{ Money::format($p['current_price']) }}</td>
                                <td class="text-right tabular-nums">{{ Money::format($p['market_value']) }}</td>
                                <td class="text-right tabular-nums {{ Money::isNegative($p['unrealized_pnl']) ? 'text-rose-600' : 'text-emerald-600' }}">{{ Money::format($p['unrealized_pnl']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="mt-6 grid grid-cols-1 gap-4 xl:grid-cols-3">
        {{-- Recent transactions --}}
        <div class="card overflow-hidden xl:col-span-2">
            <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-ink-800">{{ __('Recent transactions') }}</h2>
                <a href="{{ route('transactions.index') }}" class="text-sm font-medium text-brand-700 hover:text-brand-800">{{ __('View all') }}</a>
            </div>
            @forelse ($summary['recent_transactions'] as $tx)
                <a href="{{ route('transactions.show', $tx) }}" class="flex items-center justify-between gap-4 px-5 py-3 hover:bg-ink-50/60 {{ ! $loop->last ? 'border-b border-ink-100' : '' }}">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-ink-900">{{ $tx->type->label() }}</p>
                        <p class="truncate text-xs text-ink-400">{{ $tx->description }} · {{ $tx->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold tabular-nums">{{ Money::format($tx->amount, $tx->currency) }}</p>
                        <x-status-badge :status="$tx->status" />
                    </div>
                </a>
            @empty
                <p class="px-5 py-10 text-center text-sm text-ink-400">{{ __('No transactions yet. Your deposits, trades and withdrawals will appear here.') }}</p>
            @endforelse
        </div>

        {{-- Recent trades --}}
        <div class="card overflow-hidden">
            <div class="border-b border-ink-100 px-5 py-4"><h2 class="text-sm font-semibold text-ink-800">{{ __('Recent trades') }}</h2></div>
            @forelse ($summary['recent_trades'] as $trade)
                <div class="flex items-center justify-between px-5 py-3 {{ ! $loop->last ? 'border-b border-ink-100' : '' }}">
                    <div>
                        <p class="text-sm font-medium text-ink-900">{{ $trade->market->symbol }}</p>
                        <p class="text-xs {{ $trade->side->value === 'buy' ? 'text-emerald-600' : 'text-rose-600' }}">{{ $trade->side->label() }} · {{ $trade->executed_at->diffForHumans() }}</p>
                    </div>
                    <p class="text-sm tabular-nums">{{ rtrim(rtrim($trade->quantity, '0'), '.') }} @ {{ Money::format($trade->price) }}</p>
                </div>
            @empty
                <p class="px-5 py-10 text-center text-sm text-ink-400">{{ __('No trades yet.') }}</p>
            @endforelse
        </div>
    </div>
</x-layouts.app>
