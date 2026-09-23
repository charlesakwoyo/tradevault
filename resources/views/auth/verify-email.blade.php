<x-layouts.guest :title="__('Verify your email')">
    <div class="flex items-start gap-3 rounded-xl bg-brand-50 p-4 text-sm text-brand-900">
        <x-icon name="mail" class="mt-0.5 size-5 text-brand-600" />
        <p>{{ __('We sent a verification link to :email. Click it to activate your account. Check your spam folder if it has not arrived.', ['email' => auth()->user()->email]) }}</p>
    </div>

    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-primary w-full sm:w-auto">{{ __('Resend verification email') }}</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm font-medium text-ink-500 hover:text-ink-700">{{ __('Log out') }}</button>
        </form>
    </div>
</x-layouts.guest>
