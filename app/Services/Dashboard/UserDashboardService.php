<?php

namespace App\Services\Dashboard;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Models\Position;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;

/**
 * Customer dashboard figures. Every number is derived from ledger, position
 * and trade records; nothing is estimated or synthesized.
 */
class UserDashboardService
{
    private const CHART_DAYS = 30;

    /**
     * @return array<string, mixed>
     */
    public function summary(User $user): array
    {
        $currency = config('tradevault.base_currency');
        $user->load(['wallets', 'kycProfile']);

        $available = $user->wallet(WalletType::Available, $currency)?->balance ?? '0';
        $reserved = $user->wallet(WalletType::Reserved, $currency)?->balance ?? '0';
        $trading = $user->wallet(WalletType::Trading, $currency)?->balance ?? '0';

        $positions = $this->valuedPositions($user, $currency);
        $positionsValue = Money::sum(array_column($positions, 'market_value'));
        $unrealized = Money::sum(array_column($positions, 'unrealized_pnl'));
        $realized = Money::normalize((string) (Position::query()
            ->where('user_id', $user->id)
            ->whereHas('market', fn (Builder $q) => $q->where('quote_currency', $currency))
            ->sum('realized_pnl') ?: '0'));

        return [
            'currency' => $currency,
            'available' => $available,
            'reserved' => $reserved,
            'trading' => $trading,
            'total_deposited' => $this->completedTotal($user, TransactionType::Deposit, $currency),
            'total_withdrawn' => $this->completedTotal($user, TransactionType::Withdrawal, $currency),
            'portfolio_value' => Money::sum([$available, $reserved, $trading, $positionsValue]),
            'positions' => $positions,
            'open_positions' => count($positions),
            'realized_pnl' => $realized,
            'unrealized_pnl' => $unrealized,
            'uses_simulated_prices' => collect($positions)->contains('is_simulated', true),
            'kyc_status' => $user->kycStatus(),
            'recent_transactions' => $user->walletTransactions()->latest()->limit(8)->get(),
            'recent_trades' => $user->trades()->with('market')->latest('executed_at')->limit(5)->get(),
        ];
    }

    /**
     * @return array<string, array{labels: list<string>, values: list<float>}>
     */
    public function charts(User $user): array
    {
        $currency = config('tradevault.base_currency');

        $ledger = fn (TransactionType $type) => WalletTransaction::query()
            ->where('user_id', $user->id)
            ->where('currency', $currency)
            ->where('type', $type)
            ->where('status', TransactionStatus::Completed);

        $snapshots = $user->portfolioSnapshots()
            ->where('currency', $currency)
            ->where('snapshot_date', '>=', now()->subDays(89)->toDateString())
            ->orderBy('snapshot_date')
            ->get(['snapshot_date', 'total_value']);

        return [
            'deposits' => DailySeries::sum($ledger(TransactionType::Deposit), 'amount', self::CHART_DAYS),
            'withdrawals' => DailySeries::sum($ledger(TransactionType::Withdrawal), 'amount', self::CHART_DAYS),
            'trading' => DailySeries::sum(
                $user->trades()->getQuery()->whereNotNull('realized_pnl'),
                'realized_pnl',
                self::CHART_DAYS,
                'executed_at',
            ),
            'portfolio' => [
                'labels' => $snapshots->map(fn ($s) => $s->snapshot_date->format('M j'))->all(),
                'values' => $snapshots->map(fn ($s) => round((float) $s->total_value, 2))->all(),
            ],
        ];
    }

    private function completedTotal(User $user, TransactionType $type, string $currency): string
    {
        return Money::normalize((string) (WalletTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->where('currency', $currency)
            ->where('status', TransactionStatus::Completed)
            ->sum('amount') ?: '0'));
    }

    /**
     * Open positions quoted in $currency, valued at the latest recorded price.
     *
     * @return list<array<string, mixed>>
     */
    private function valuedPositions(User $user, string $currency): array
    {
        return Position::query()
            ->open()
            ->where('user_id', $user->id)
            ->whereHas('market', fn (Builder $q) => $q->where('quote_currency', $currency))
            ->with('market.latestPrice')
            ->get()
            ->map(function (Position $position) {
                $price = $position->market->latestPrice;
                $current = $price?->last ?? $position->average_entry_price;
                $marketValue = Money::mul($position->quantity, $current);
                $cost = Money::mul($position->quantity, $position->average_entry_price);

                return [
                    'symbol' => $position->market->symbol,
                    'quantity' => $position->quantity,
                    'average_entry_price' => $position->average_entry_price,
                    'current_price' => $current,
                    'market_value' => $marketValue,
                    'unrealized_pnl' => Money::sub($marketValue, $cost),
                    'is_simulated' => (bool) ($price?->is_simulated ?? true),
                ];
            })
            ->all();
    }
}
