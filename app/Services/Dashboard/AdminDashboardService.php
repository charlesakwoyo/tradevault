<?php

namespace App\Services\Dashboard;

use App\Enums\DepositStatus;
use App\Enums\KycStatus;
use App\Enums\OrderStatus;
use App\Enums\RoleName;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\WithdrawalStatus;
use App\Models\Deposit;
use App\Models\KycProfile;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Back-office KPIs, computed from the ledger and operational tables.
 * Cached briefly because several of these are full-table aggregates.
 */
class AdminDashboardService
{
    private const CACHE_SECONDS = 60;

    private const CHART_DAYS = 30;

    /**
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        $currency = config('tradevault.base_currency');

        return Cache::remember("admin:dashboard:stats:{$currency}", self::CACHE_SECONDS, fn () => [
            'currency' => $currency,
            'total_users' => $this->customers()->count(),
            'verified_users' => $this->customers()->whereHas('kycProfile', fn (Builder $q) => $q->where('status', KycStatus::Approved))->count(),
            'pending_kyc' => KycProfile::query()->whereIn('status', [KycStatus::Pending, KycStatus::UnderReview])->count(),
            'total_deposits' => $this->ledgerTotal(TransactionType::Deposit, $currency),
            'total_withdrawals' => $this->ledgerTotal(TransactionType::Withdrawal, $currency),
            'platform_fees' => $this->ledgerTotal(TransactionType::Fee, $currency),
            'trading_volume' => Money::normalize((string) (Trade::query()
                ->whereHas('market', fn (Builder $q) => $q->where('quote_currency', $currency))
                ->sum(DB::raw('quantity * price')) ?: '0')),
            'pending_deposits' => Deposit::query()->whereIn('status', [DepositStatus::Pending, DepositStatus::Processing])->count(),
            'pending_withdrawals' => Withdrawal::query()->whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::UnderReview, WithdrawalStatus::Approved])->count(),
            'active_orders' => Order::query()->whereIn('status', [OrderStatus::Pending, OrderStatus::Open, OrderStatus::PartiallyFilled])->count(),
        ]);
    }

    /**
     * @return array<string, array{labels: list<string>, values: list<float>}>
     */
    public function charts(): array
    {
        $currency = config('tradevault.base_currency');

        return Cache::remember("admin:dashboard:charts:{$currency}", self::CACHE_SECONDS, fn () => [
            'signups' => DailySeries::count($this->customers(), self::CHART_DAYS),
            'deposits' => DailySeries::sum($this->ledger(TransactionType::Deposit, $currency), 'amount', self::CHART_DAYS),
            'withdrawals' => DailySeries::sum($this->ledger(TransactionType::Withdrawal, $currency), 'amount', self::CHART_DAYS),
        ]);
    }

    /** @return Builder<User> */
    private function customers(): Builder
    {
        return User::query()->role(RoleName::User->value);
    }

    /** @return Builder<WalletTransaction> */
    private function ledger(TransactionType $type, string $currency): Builder
    {
        return WalletTransaction::query()
            ->where('type', $type)
            ->where('currency', $currency)
            ->where('status', TransactionStatus::Completed);
    }

    private function ledgerTotal(TransactionType $type, string $currency): string
    {
        return Money::normalize((string) ($this->ledger($type, $currency)->sum('amount') ?: '0'));
    }
}
