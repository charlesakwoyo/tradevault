<?php

namespace App\Services\Wallet;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\Wallet\InsufficientFundsException;
use App\Exceptions\Wallet\LedgerException;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The only code path allowed to change a wallet balance.
 *
 * Every posting is a balanced set of legs (signed amounts summing to zero)
 * written atomically: wallets are row-locked in a fixed order, the new
 * balances are checked, a journal header plus one ledger entry per leg are
 * appended, and the cached balances are updated. A posting's `reference` is
 * unique, so replaying the same business event returns the original
 * transaction instead of moving money twice.
 */
class LedgerService
{
    /**
     * @param  list<array{0: Wallet, 1: string}>  $legs  [wallet, signed amount] pairs; + credits, - debits.
     * @param  array<string, mixed>  $meta
     */
    public function post(
        TransactionType $type,
        ?User $user,
        string $amount,
        string $currency,
        array $legs,
        string $reference,
        string $description,
        ?Model $source = null,
        ?User $createdBy = null,
        array $meta = [],
    ): WalletTransaction {
        $this->assertBalanced($legs, $currency);

        return DB::transaction(function () use ($type, $user, $amount, $currency, $legs, $reference, $description, $source, $createdBy, $meta) {
            // Lock in ascending id order so concurrent postings touching the same
            // wallets always acquire locks in the same sequence (no deadlocks).
            $walletIds = collect($legs)->map(fn (array $leg) => $leg[0]->getKey())->unique()->sort()->values();
            $locked = Wallet::query()->whereKey($walletIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            // Idempotency: checked after acquiring locks so a concurrent duplicate waits and then sees the first posting.
            if ($existing = WalletTransaction::query()->where('reference', $reference)->first()) {
                Log::info('Ledger posting replay ignored', ['reference' => $reference]);

                return $existing;
            }

            $newBalances = [];
            foreach ($legs as [$wallet, $legAmount]) {
                $id = $wallet->getKey();
                $current = $newBalances[$id] ?? $locked[$id]->balance;
                $newBalances[$id] = Money::add($current, $legAmount);
            }

            foreach ($newBalances as $id => $balance) {
                $wallet = $locked[$id];
                if (Money::isNegative($balance) && ! $wallet->allowsNegativeBalance()) {
                    throw InsufficientFundsException::for($wallet, Money::sub($wallet->balance, $balance));
                }
            }

            $transaction = WalletTransaction::unguarded(fn () => WalletTransaction::query()->create([
                'user_id' => $user?->getKey(),
                'type' => $type,
                'amount' => Money::normalize($amount),
                'currency' => $currency,
                'status' => TransactionStatus::Completed,
                'reference' => $reference,
                'description' => $description,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'created_by' => $createdBy?->getKey(),
                'meta' => $meta ?: null,
            ]));

            $running = [];
            foreach ($legs as [$wallet, $legAmount]) {
                $id = $wallet->getKey();
                $running[$id] = Money::add($running[$id] ?? $locked[$id]->balance, $legAmount);

                LedgerEntry::unguarded(fn () => LedgerEntry::query()->create([
                    'wallet_transaction_id' => $transaction->getKey(),
                    'wallet_id' => $id,
                    'amount' => Money::normalize($legAmount),
                    'balance_after' => $running[$id],
                    'created_at' => now(),
                ]));
            }

            foreach ($newBalances as $id => $balance) {
                $locked[$id]->forceFill(['balance' => $balance])->save();
            }

            // Keep caller-held instances in sync with the database.
            foreach ($legs as [$wallet]) {
                $wallet->setRawAttributes($locked[$wallet->getKey()]->getAttributes(), true);
            }

            return $transaction;
        });
    }

    /**
     * Recompute a wallet's balance from its entries. Used by `ledger:verify`.
     */
    public function derivedBalance(Wallet $wallet): string
    {
        return Money::normalize((string) ($wallet->entries()->sum('amount') ?: '0'));
    }

    /** @param  list<array{0: Wallet, 1: string}>  $legs */
    private function assertBalanced(array $legs, string $currency): void
    {
        if (count($legs) < 2) {
            throw new LedgerException('A posting needs at least two legs.');
        }

        $total = '0';
        foreach ($legs as [$wallet, $amount]) {
            if (! $wallet instanceof Wallet || ! $wallet->exists) {
                throw new LedgerException('Every leg must reference a persisted wallet.');
            }
            if ($wallet->currency !== $currency) {
                throw new LedgerException("Leg currency {$wallet->currency} does not match posting currency {$currency}.");
            }
            if (Money::isZero($amount)) {
                throw new LedgerException('Ledger legs must be non-zero.');
            }
            $total = Money::add($total, $amount);
        }

        if (! Money::isZero($total)) {
            throw new LedgerException("Unbalanced posting: legs sum to {$total}.");
        }
    }
}
