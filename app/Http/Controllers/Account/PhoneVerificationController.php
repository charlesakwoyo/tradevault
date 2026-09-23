<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\PhoneVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PhoneVerificationController extends Controller
{
    public function __construct(private readonly PhoneVerificationService $verification) {}

    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedPhone()) {
            return back()->with('status', __('Your phone number is already verified.'));
        }

        $this->verification->sendCode($request->user());

        return back()->with('status', 'phone-code-sent');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate(['code' => ['required', 'digits:6']]);

        $this->verification->verify($request->user(), $validated['code']);

        return back()->with('status', 'phone-verified');
    }
}
