<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ends the session of an account that was suspended while logged in and
 * tells the user why, rather than failing silently.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isSuspended()) {
            $message = __('This account is suspended. Please contact support at :email.', ['email' => config('tradevault.support_email')]);

            if ($request->expectsJson()) {
                $user->currentAccessToken()?->delete();

                return response()->json(['message' => $message], Response::HTTP_FORBIDDEN);
            }

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
