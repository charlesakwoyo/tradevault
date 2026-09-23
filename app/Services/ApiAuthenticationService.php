<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Events\RecoveryCodeReplaced;
use Laravel\Fortify\Fortify;

/**
 * Credential + optional 2FA verification for Sanctum token logins. Fires the
 * same auth events as the web login so auditing is identical.
 */
class ApiAuthenticationService
{
    public function __construct(private readonly TwoFactorAuthenticationProvider $twoFactor) {}

    /**
     * @param  array{email: string, password: string, code?: string|null, recovery_code?: string|null}  $credentials
     *
     * @throws ValidationException
     */
    public function attempt(array $credentials): User
    {
        $user = User::query()->where('email', Str::lower($credentials['email']))->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            event(new Failed('sanctum', $user, ['email' => $credentials['email']]));

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        if ($user->isSuspended()) {
            throw ValidationException::withMessages([
                'email' => __('This account is suspended. Please contact support at :email.', ['email' => config('tradevault.support_email')]),
            ]);
        }

        if ($user->hasTwoFactorEnabled()) {
            $this->verifySecondFactor($user, $credentials['code'] ?? null, $credentials['recovery_code'] ?? null);
        }

        event(new Login('sanctum', $user, false));

        return $user;
    }

    private function verifySecondFactor(User $user, ?string $code, ?string $recoveryCode): void
    {
        if ($code) {
            $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);

            if ($this->twoFactor->verify($secret, $code)) {
                return;
            }
        } elseif ($recoveryCode) {
            $match = collect($user->recoveryCodes())->first(fn (string $stored) => hash_equals($stored, $recoveryCode));

            if ($match) {
                $user->replaceRecoveryCode($match);
                event(new RecoveryCodeReplaced($user, $match));

                return;
            }
        } else {
            throw ValidationException::withMessages([
                'code' => __('Two-factor authentication code required.'),
            ]);
        }

        event(new Failed('sanctum', $user, ['email' => $user->email]));

        throw ValidationException::withMessages(['code' => __('The provided two-factor authentication code was invalid.')]);
    }
}
