<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staff accounts do not hold customer wallets or trade; send them to the back office.
 */
class RedirectStaffToBackOffice
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isStaff() && ! $user->isCustomer()) {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
