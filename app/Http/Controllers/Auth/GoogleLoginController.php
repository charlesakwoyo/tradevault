<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Countries;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * "Sign in with Google". Existing customers are matched by Google ID, or by a
 * verified email on both sides; newcomers finish a short form collecting the
 * details registration requires (phone, country, date of birth, consent).
 */
class GoogleLoginController extends Controller
{
    /** Session key holding the Google profile of a sign-up in progress. */
    private const PENDING = 'google_signup';

    /**
     * Both credentials are needed: the ID to send users to Google, the secret to complete the callback.
     */
    public static function isConfigured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    public function redirect(): SymfonyRedirect
    {
        abort_unless(self::isConfigured(), 404);

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): Response
    {
        abort_unless(self::isConfigured(), 404);

        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            report($e);

            return $this->backToLogin(__('Google sign-in failed. Please try again.'));
        }

        $email = Str::lower((string) $google->getEmail());

        if ($email === '' || ! ($google->getRaw()['email_verified'] ?? false)) {
            return $this->backToLogin(__('Your Google account email is not verified.'));
        }

        $user = User::query()->where('google_id', $google->getId())->first()
            ?? User::query()->where('email', $email)->first();

        if (! $user) {
            if (! config('tradevault.registration.enabled')) {
                return $this->backToLogin(__('Registration is currently closed.'));
            }

            $request->session()->put(self::PENDING, [
                'google_id' => (string) $google->getId(),
                'email' => $email,
                'name' => (string) $google->getName(),
            ]);

            return redirect()->route('auth.google.complete');
        }

        if ($user->google_id === null) {
            // Only link to an account whose owner has proven they control this email;
            // otherwise someone could pre-register a victim's address and share the account.
            if (! $user->hasVerifiedEmail()) {
                return $this->backToLogin(__('An account with this email already exists. Sign in with your password (or reset it) and verify your email, then you can use Google.'));
            }

            $user->forceFill(['google_id' => (string) $google->getId()])->save();
        } elseif ($user->google_id !== (string) $google->getId()) {
            return $this->backToLogin(__('This account is linked to a different Google account.'));
        }

        return $this->logIn($request, $user);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->get(self::PENDING);

        if (! $pending) {
            return redirect()->route('login');
        }

        return view('auth.google-complete', [
            'pending' => $pending,
            'countries' => Countries::permitted(),
        ]);
    }

    public function store(Request $request, CreateNewUser $creator): Response
    {
        $pending = $request->session()->get(self::PENDING);

        if (! $pending) {
            return redirect()->route('login');
        }

        $user = $creator->createFromGoogle(
            $request->only(['name', 'phone', 'country', 'date_of_birth', 'terms', 'privacy']),
            $pending['email'],
            $pending['google_id'],
        );

        $request->session()->forget(self::PENDING);
        event(new Registered($user));

        return $this->logIn($request, $user);
    }

    /**
     * Same gates as the password login: suspended accounts are refused and
     * two-factor users must still pass the 2FA challenge.
     */
    private function logIn(Request $request, User $user): Response
    {
        if ($user->isSuspended()) {
            return $this->backToLogin(__('This account is suspended. Please contact support at :email.', [
                'email' => config('tradevault.support_email'),
            ]));
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            $request->session()->put(['login.id' => $user->getKey(), 'login.remember' => false]);
            TwoFactorAuthenticationChallenged::dispatch($user);

            return redirect()->route('two-factor.login');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return app(LoginResponse::class)->toResponse($request);
    }

    private function backToLogin(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
