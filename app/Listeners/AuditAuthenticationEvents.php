<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Str;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;

/**
 * Writes authentication and account-security events to the audit trail.
 * Each handle* method is registered automatically by event discovery.
 */
class AuditAuthenticationEvents
{
    public function __construct(private readonly AuditService $audit) {}

    public function handleRegistered(Registered $event): void
    {
        $this->audit->log('auth.registered', $event->user, actor: $this->asUser($event->user));
    }

    public function handleLogin(Login $event): void
    {
        $user = $this->asUser($event->user);

        if ($user) {
            $user->forceFill(['last_login_at' => now(), 'last_login_ip' => request()->ip()])->saveQuietly();
        }

        $this->audit->log('auth.login', $user, meta: ['guard' => $event->guard, 'remember' => $event->remember], actor: $user);
    }

    public function handleLogout(Logout $event): void
    {
        $user = $this->asUser($event->user);

        $this->audit->log('auth.logout', $user, actor: $user);
    }

    /**
     * Custom authentication callbacks report failures without a user, so the
     * account is resolved from the submitted email to attribute the attempt.
     */
    public function handleFailed(Failed $event): void
    {
        $email = $event->credentials['email'] ?? null;
        $user = $this->asUser($event->user)
            ?? (is_string($email) ? User::query()->where('email', Str::lower($email))->first() : null);

        $this->audit->log('auth.failed', $user, meta: [
            'guard' => $event->guard,
            'email' => $email,
            'known_account' => $user !== null,
        ], actor: $user);
    }

    public function handleLockout(Lockout $event): void
    {
        $this->audit->log('auth.lockout', meta: ['email' => $event->request->input('email')]);
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        $user = $this->asUser($event->user);

        $this->audit->log('user.password_reset', $user, actor: $user);
    }

    public function handleVerified(Verified $event): void
    {
        $user = $this->asUser($event->user);

        $this->audit->log('user.email_verified', $user, actor: $user);
    }

    public function handleTwoFactorConfirmed(TwoFactorAuthenticationConfirmed $event): void
    {
        $this->audit->log('security.2fa_enabled', $event->user, actor: $event->user);
    }

    public function handleTwoFactorDisabled(TwoFactorAuthenticationDisabled $event): void
    {
        $this->audit->log('security.2fa_disabled', $event->user, actor: $event->user);
    }

    public function handleRecoveryCodesGenerated(RecoveryCodesGenerated $event): void
    {
        $this->audit->log('security.2fa_recovery_codes_regenerated', $event->user, actor: $event->user);
    }

    public function handleTwoFactorFailed(TwoFactorAuthenticationFailed $event): void
    {
        $this->audit->log('auth.2fa_failed', $event->user, actor: $event->user);
    }

    private function asUser(mixed $user): ?User
    {
        return $user instanceof User ? $user : null;
    }
}
