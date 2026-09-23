<?php

namespace App\Services;

use App\Models\User;
use App\Services\Sms\SmsGateway;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * One-time-code phone verification. Codes are stored hashed, expire after
 * ten minutes, and are invalidated after too many wrong guesses.
 */
class PhoneVerificationService
{
    private const TTL_MINUTES = 10;

    private const MAX_ATTEMPTS = 5;

    private const MAX_SENDS_PER_HOUR = 5;

    public function __construct(
        private readonly SmsGateway $sms,
        private readonly AuditService $audit,
    ) {}

    public function sendCode(User $user): void
    {
        $sendKey = "phone-otp-send:{$user->id}";

        if (RateLimiter::tooManyAttempts($sendKey, self::MAX_SENDS_PER_HOUR)) {
            throw ValidationException::withMessages([
                'code' => __('Too many codes requested. Try again in :minutes minutes.', [
                    'minutes' => (int) ceil(RateLimiter::availableIn($sendKey) / 60),
                ]),
            ]);
        }

        RateLimiter::hit($sendKey, 3600);

        $code = (string) random_int(100000, 999999);

        Cache::put($this->cacheKey($user), [
            'hash' => Hash::make($code),
            'phone' => $user->phone,
            'attempts' => 0,
        ], now()->addMinutes(self::TTL_MINUTES));

        $this->sms->send($user->phone, __(':app verification code: :code. It expires in :minutes minutes. Never share this code.', [
            'app' => config('app.name'),
            'code' => $code,
            'minutes' => self::TTL_MINUTES,
        ]));
    }

    public function verify(User $user, string $code): void
    {
        $key = $this->cacheKey($user);
        $pending = Cache::get($key);

        if (! $pending || $pending['phone'] !== $user->phone) {
            throw ValidationException::withMessages(['code' => __('The code has expired. Request a new one.')]);
        }

        if (! Hash::check($code, $pending['hash'])) {
            $pending['attempts']++;

            if ($pending['attempts'] >= self::MAX_ATTEMPTS) {
                Cache::forget($key);
                throw ValidationException::withMessages(['code' => __('Too many incorrect attempts. Request a new code.')]);
            }

            Cache::put($key, $pending, now()->addMinutes(self::TTL_MINUTES));
            throw ValidationException::withMessages(['code' => __('The code is incorrect.')]);
        }

        Cache::forget($key);
        $user->forceFill(['phone_verified_at' => now()])->save();
        $this->audit->log('user.phone_verified', $user, actor: $user);
    }

    private function cacheKey(User $user): string
    {
        return "phone-otp:{$user->id}";
    }
}
