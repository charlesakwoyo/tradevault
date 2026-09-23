<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\Countries;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Profile and security pages. The forms post to Fortify's endpoints
 * (profile information, password, two-factor), which own the logic.
 */
class AccountController extends Controller
{
    public function profile(Request $request): View
    {
        return view('account.profile', [
            'user' => $request->user(),
            'countryName' => Countries::name($request->user()->country),
        ]);
    }

    public function security(Request $request): View
    {
        return view('account.security', [
            'user' => $request->user(),
            'recentActivity' => AuditLog::query()
                ->where('user_id', $request->user()->id)
                ->where(fn ($q) => $q->where('action', 'like', 'auth.%')->orWhere('action', 'like', 'security.%')->orWhere('action', 'like', 'user.%'))
                ->latest('created_at')
                ->limit(10)
                ->get(),
        ]);
    }
}
