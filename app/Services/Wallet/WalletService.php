<?php

namespace App\Services\Wallet;

use App\Enums\SystemAccount;
use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\AuditService;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class WalletService
{
    /** Wallet types every customer gets per supported currency. */
    public const USER_WALLET_TYPES = [WalletType::Available, WalletType::Reserved, WalletType::Trading];

    public function __construct(
        private readonly LedgerService $ledger,
        private readonly AuditService $audit,
    ) {}

    /** @return Collection<int, Wallet> */
    public function ensureUserWallets(User $user): Collection
    {
        foreach (config('tradevault.currencies') as $currency) {
            foreach (self::USER_WALLET_TYPES as $type) {
                $this->userWallet($user, $type, $currency);
            }
        }

        return $user->wallets()->get();
    }

    public function userWallet(User $user, WalletType $type, string $currency): Wallet
    {
        $this->assertSupportedCurrency($currency);

        return Wallet::unguarded(fn () => Wallet::query()->createOrFirst(
            ['user_id' => $user->getKey(), 'type' => $type, 'currency' => $currency],
        ));
    }

    public function systemWallet(SystemAccount $account, string $currency): Wallet
    {
        $this->assertSupportedCurrency($currency);

        return Wallet::unguarded(fn () => Wallet::query()->createOrFirst(
            ['system_code' => $account->value, 'currency' => $currency],
            ['type' => WalletType::System],
        ));
    }

    /**
     * Per-currency balance summary for a user.
     *
     * @return array<string, array<string, string>> currency => [wallet type => balance]
     */
    public function balances(User $user): array
    {
        $summary = [];

        foreach ($user->wallets()->get() as $wallet) {
            $summary[$wallet->currency][$wallet->type->value] = $wallet->balance;
        }

        return $summary;
    }

    /**
     * Audited manual adjustment of a user's available balance.
     *
     * A positive amount credits the user, a negative amount debits them. The
     * counter-leg goes to the Adjustments system account, so the change is
     * visible in the ledger and never "silent".
     */
    public function adjust(User $user, string $currency, string $amount, string $reason, User $admin): WalletTransaction
    {
        $amount = Money::normalize($amount);
        $reason = trim($reason);

        if (Money::isZero($amount)) {
            throw new InvalidArgumentException('Adjustment amount must be non-zero.');
        }
        if (mb_strlen($reason) < 10) {
            throw new InvalidArgumentException('An adjustment requires a descriptive reason (min 10 characters).');
        }

        $userWallet = $this->userWallet($user, WalletType::Available, $currency);
        $counter = $this->systemWallet(SystemAccount::Adjustments, $currency);
        $before = $userWallet->balance;

        $transaction = $this->ledger->post(
            type: TransactionType::Adjustment,
            user: $user,
            amount: ltrim($amount, '-'),
            currency: $currency,
            legs: [[$userWallet, $amount], [$counter, Money::negate($amount)]],
            reference: 'ADJ-'.Str::upper(Str::ulid()),
            description: 'Balance adjustment: '.$reason,
            createdBy: $admin,
            meta: ['reason' => $reason, 'direction' => Money::isPositive($amount) ? 'credit' : 'debit'],
        );

        $this->audit->log(
            'wallet.adjusted',
            $userWallet,
            ['balance' => $before],
            ['balance' => $userWallet->balance],
            ['reason' => $reason, 'amount' => $amount, 'currency' => $currency, 'transaction' => $transaction->uuid, 'target_user_id' => $user->getKey()],
            $admin,
        );

        return $transaction;
    }

    private function assertSupportedCurrency(string $currency): void
    {
        if (! in_array($currency, config('tradevault.currencies'), true)) {
            throw new InvalidArgumentException("Unsupported currency [{$currency}].");
        }
    }
}
