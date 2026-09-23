<?php

namespace Tests;

use App\Enums\RoleName;
use App\Enums\SystemAccount;
use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Wallet\LedgerService;
use App\Services\Wallet\WalletService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * Seed roles & permissions for tests that use RefreshDatabase.
     */
    protected function seedRoles(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function customer(array $attributes = []): User
    {
        return User::factory()->customer()->create($attributes);
    }

    protected function staff(RoleName $role = RoleName::Admin, array $attributes = []): User
    {
        return User::factory()->staff($role)->create($attributes);
    }

    /**
     * Credit a user's available balance through the ledger, the same way a
     * confirmed deposit would (counter-leg: deposits clearing account).
     */
    protected function fund(User $user, string $amount, ?string $currency = null): WalletTransaction
    {
        $currency ??= config('tradevault.base_currency');
        $wallets = app(WalletService::class);

        return app(LedgerService::class)->post(
            type: TransactionType::Deposit,
            user: $user,
            amount: $amount,
            currency: $currency,
            legs: [
                [$wallets->userWallet($user, WalletType::Available, $currency), $amount],
                [$wallets->systemWallet(SystemAccount::DepositsClearing, $currency), '-'.$amount],
            ],
            reference: 'TEST-DEP-'.Str::ulid(),
            description: 'Test deposit',
        );
    }
}
