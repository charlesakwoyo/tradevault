<?php

namespace Database\Factories;

use App\Enums\WalletType;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Creates empty wallets only. Balances must come from LedgerService postings,
 * so there is intentionally no way to create a wallet with money in it here.
 *
 * @extends Factory<Wallet>
 */
class WalletFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => WalletType::Available,
            'currency' => config('tradevault.base_currency'),
            'balance' => '0',
        ];
    }
}
