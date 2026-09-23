<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends staff to the back office and customers to their dashboard.
 */
class LoginResponse implements LoginResponseContract, TwoFactorLoginResponseContract
{
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false]);
        }

        $home = $request->user()?->can('admin.access') ? route('admin.dashboard') : route('dashboard');

        return redirect()->intended($home);
    }
}
