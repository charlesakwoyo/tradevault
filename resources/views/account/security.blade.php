@php $area = auth()->user()->isStaff() && ! auth()->user()->isCustomer() ? 'admin' : 'customer'; @endphp

<x-layouts.app :title="__('Security')" :area="$area">
    <div class="grid max-w-5xl grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Password (Fortify: PUT /user/password) --}}
        <section class="card p-6">
            <h2 class="text-base font-semibold text-ink-900">{{ __('Change password') }}</h2>
            <form method="POST" action="{{ route('user-password.update') }}" class="mt-5 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="current_password" class="form-label">{{ __('Current password') }}</label>
                    <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="form-input">
                    @error('current_password', 'updatePassword') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password" class="form-label">{{ __('New password') }}</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password" class="form-input">
                    <p class="mt-1 text-xs text-ink-400">{{ __('At least 12 characters with upper and lower case letters, a number and a symbol.') }}</p>
                    @error('password', 'updatePassword') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="form-label">{{ __('Confirm new password') }}</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="form-input">
                </div>
                <button type="submit" class="btn-primary">{{ __('Update password') }}</button>
            </form>
        </section>

        {{-- Two-factor authentication (Fortify) --}}
        <section class="card p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-ink-900">{{ __('Two-factor authentication') }}</h2>
                    <p class="mt-1 text-sm text-ink-500">{{ __('Protect your account with a code from an authenticator app each time you sign in.') }}</p>
                </div>
                <x-status-badge :status="$user->hasTwoFactorEnabled() ? 'active' : 'pending'">{{ $user->hasTwoFactorEnabled() ? __('On') : __('Off') }}</x-status-badge>
            </div>

            @if (! $user->two_factor_secret)
                <form method="POST" action="{{ route('two-factor.enable') }}" class="mt-5">
                    @csrf
                    <button type="submit" class="btn-primary"><x-icon name="lock" class="size-4" /> {{ __('Enable 2FA') }}</button>
                </form>
            @elseif (! $user->hasTwoFactorEnabled())
                <div class="mt-5 space-y-4">
                    <p class="text-sm text-ink-700">{{ __('1. Scan this QR code with Google Authenticator, 1Password, Authy or similar.') }}</p>
                    <div class="inline-block rounded-xl border border-ink-100 bg-white p-3">{!! $user->twoFactorQrCodeSvg() !!}</div>
                    <p class="text-sm text-ink-700">{{ __('2. Enter the 6-digit code it shows to confirm.') }}</p>
                    <form method="POST" action="{{ route('two-factor.confirm') }}" class="flex flex-col gap-3 sm:flex-row sm:items-start">
                        @csrf
                        <div class="sm:w-48">
                            <input name="code" type="text" inputmode="numeric" autocomplete="one-time-code" required class="form-input tracking-[0.3em]" aria-label="{{ __('Authentication code') }}">
                            @error('code', 'confirmTwoFactorAuthentication') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="btn-primary">{{ __('Confirm') }}</button>
                    </form>
                </div>
            @else
                <div class="mt-5 space-y-4" x-data="{ showCodes: {{ session('status') === 'recovery-codes-generated' ? 'true' : 'false' }} }">
                    <button type="button" class="btn-secondary" @click="showCodes = ! showCodes">{{ __('Show recovery codes') }}</button>
                    <div x-cloak x-show="showCodes" class="rounded-xl bg-ink-50 p-4">
                        <p class="text-xs text-ink-500">{{ __('Store these somewhere safe. Each code can be used once if you lose your device.') }}</p>
                        <ul class="mt-3 grid grid-cols-2 gap-1 font-mono text-sm text-ink-800">
                            @foreach ($user->recoveryCodes() as $code)
                                <li>{{ $code }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}">
                            @csrf
                            <button type="submit" class="btn-secondary">{{ __('Regenerate codes') }}</button>
                        </form>
                        <form method="POST" action="{{ route('two-factor.disable') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-danger">{{ __('Disable 2FA') }}</button>
                        </form>
                    </div>
                </div>
            @endif
        </section>

        {{-- Recent security activity from the audit log --}}
        <section class="card overflow-hidden lg:col-span-2">
            <div class="border-b border-ink-100 px-6 py-4">
                <h2 class="text-base font-semibold text-ink-900">{{ __('Recent account activity') }}</h2>
                <p class="mt-0.5 text-sm text-ink-500">{{ __('If you do not recognise an event, change your password and contact support.') }}</p>
            </div>
            <ul class="divide-y divide-ink-100">
                @forelse ($recentActivity as $event)
                    <li class="flex flex-col gap-1 px-6 py-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                        <span class="font-medium text-ink-800">{{ \Illuminate\Support\Str::of($event->action)->after('.')->replace('_', ' ')->ucfirst() }}</span>
                        <span class="text-ink-400">{{ $event->ip_address ?? '—' }} · {{ $event->created_at->diffForHumans() }}</span>
                    </li>
                @empty
                    <li class="px-6 py-8 text-center text-sm text-ink-400">{{ __('No activity recorded yet.') }}</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-layouts.app>
