<?php

namespace Database\Factories;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Wallet\WalletService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone' => '+2547'.fake()->unique()->numerify('########'),
            'country' => 'KE',
            'date_of_birth' => fake()->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
            'password' => static::$password ??= Hash::make('password'),
            'status' => UserStatus::Active,
            'terms_accepted_at' => now(),
            'terms_version' => config('tradevault.legal.terms_version'),
            'privacy_accepted_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function phoneVerified(): static
    {
        return $this->state(fn (array $attributes) => ['phone_verified_at' => now()]);
    }

    public function suspended(string $reason = 'Compliance review'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Suspended,
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);
    }

    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['recovery-code-1', 'recovery-code-2'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /** A customer with the user role and a full set of wallets. */
    public function customer(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole(RoleName::User->value);
            app(WalletService::class)->ensureUserWallets($user);
        });
    }

    public function staff(RoleName $role = RoleName::Admin): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole($role->value));
    }
}
