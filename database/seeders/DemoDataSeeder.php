<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\KycProfile;
use App\Models\User;
use App\Services\Wallet\WalletService;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * LOCAL DEVELOPMENT ONLY. Creates one account per role and two demo
 * customers. Demo funds are posted through the ledger as a clearly labelled
 * sandbox adjustment; they are not real money and never leave the sandbox.
 */
class DemoDataSeeder extends Seeder
{
    public const PASSWORD = 'Sandbox!Pass2026';

    public function run(WalletService $wallets): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DemoDataSeeder must never run in production.');
        }

        $staff = [
            RoleName::Admin->value => ['Platform Admin', 'admin@tradevault.test'],
            RoleName::Support->value => ['Support Agent', 'support@tradevault.test'],
            RoleName::Finance->value => ['Finance Manager', 'finance@tradevault.test'],
            RoleName::Trading->value => ['Trading Manager', 'trading@tradevault.test'],
        ];

        $admin = null;
        foreach ($staff as $role => [$name, $email]) {
            $user = User::factory()->phoneVerified()->create(['name' => $name, 'email' => $email, 'password' => self::PASSWORD]);
            $user->assignRole($role);
            $admin ??= $user;
        }

        $verified = User::factory()->phoneVerified()->customer()->create([
            'name' => 'Demo Customer', 'email' => 'customer@tradevault.test', 'password' => self::PASSWORD,
        ]);
        KycProfile::factory()->approved()->for($verified)->create(['legal_name' => 'Demo Customer']);

        User::factory()->customer()->create([
            'name' => 'New Customer', 'email' => 'new@tradevault.test', 'password' => self::PASSWORD,
        ]);

        $wallets->adjust(
            $verified,
            config('tradevault.base_currency'),
            '10000',
            'SANDBOX demo credit for local testing - not real money',
            $admin,
        );

        $this->command?->info('Demo accounts created. Password for all: '.self::PASSWORD);
    }
}
