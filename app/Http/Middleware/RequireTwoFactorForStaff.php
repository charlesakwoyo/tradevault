<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Back-office users must have confirmed 2FA before touching customer data
 * or money (configurable via SECURITY_REQUIRE_STAFF_2FA).
 */
class RequireTwoFactorForStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (config('security.require_staff_2fa') && $user && ! $user->hasTwoFactorEnabled()) {
            return redirect()->route('account.security')
                ->with('warning', __('Staff accounts must enable two-factor authentication before accessing the back office.'));
        }

        return $next($request);
    }
}
