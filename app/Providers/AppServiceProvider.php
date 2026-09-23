<?php

namespace App\Providers;

use App\Services\Sms\LogSmsGateway;
use App\Services\Sms\SmsGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SmsGateway::class, fn () => match (config('services.sms.driver')) {
            'log' => new LogSmsGateway,
            default => throw new InvalidArgumentException('Unsupported SMS driver ['.config('services.sms.driver').'].'),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        if ($this->app->isProduction()) {
            URL::forceHttps();
        }

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('api-auth', fn (Request $request) => [
            Limit::perMinute(5)->by(Str::lower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by('api-auth-ip|'.$request->ip()),
        ]);

        Password::defaults(function () {
            $rule = Password::min(12)->letters()->mixedCase()->numbers()->symbols()->max(128);

            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });
    }
}
