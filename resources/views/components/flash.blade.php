@php
    $messages = [
        'profile-information-updated' => __('Your profile has been updated.'),
        'password-updated' => __('Your password has been changed.'),
        'two-factor-authentication-enabled' => __('Scan the QR code with your authenticator app, then confirm with a code.'),
        'two-factor-authentication-confirmed' => __('Two-factor authentication is now enabled.'),
        'two-factor-authentication-disabled' => __('Two-factor authentication has been disabled.'),
        'recovery-codes-generated' => __('New recovery codes generated. Store them somewhere safe.'),
        'verification-link-sent' => __('A new verification link has been sent to your email address.'),
        'phone-code-sent' => __('We sent a 6-digit code to your phone.'),
        'phone-verified' => __('Your phone number is verified.'),
    ];
    $status = session('status');
    $status = $messages[$status] ?? $status;
@endphp

@if ($status)
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" role="status"
        class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        <x-icon name="check" class="mt-0.5 size-4" />
        <span class="flex-1">{{ $status }}</span>
        <button type="button" @click="show = false" class="text-emerald-600 hover:text-emerald-800" aria-label="{{ __('Dismiss') }}"><x-icon name="x" class="size-4" /></button>
    </div>
@endif

@if (session('warning'))
    <div role="alert" class="mb-6 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <x-icon name="alert" class="mt-0.5 size-4" />
        <span>{{ session('warning') }}</span>
    </div>
@endif
