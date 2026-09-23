@php $area = auth()->user()->isStaff() && ! auth()->user()->isCustomer() ? 'admin' : 'customer'; @endphp

<x-layouts.app :title="__('Profile')" :area="$area">
    <div class="grid max-w-5xl grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            {{-- Personal details (Fortify: PUT /user/profile-information) --}}
            <section class="card p-6">
                <h2 class="text-base font-semibold text-ink-900">{{ __('Personal details') }}</h2>
                <p class="mt-1 text-sm text-ink-500">{{ __('Changing your email or phone number requires verifying it again.') }}</p>

                <form method="POST" action="{{ route('user-profile-information.update') }}" class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
                    @csrf
                    @method('PUT')

                    <div class="sm:col-span-2">
                        <label for="name" class="form-label">{{ __('Full legal name') }}</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="name" class="form-input">
                        @error('name', 'updateProfileInformation') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="email" class="form-label">{{ __('Email') }}</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="email" class="form-input">
                        @error('email', 'updateProfileInformation') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="phone" class="form-label">{{ __('Phone number') }}</label>
                        <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" required autocomplete="tel" class="form-input">
                        @error('phone', 'updateProfileInformation') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <span class="form-label">{{ __('Country of residence') }}</span>
                        <p class="rounded-lg bg-ink-50 px-3 py-2.5 text-sm text-ink-600">{{ $countryName }}</p>
                    </div>
                    <div>
                        <span class="form-label">{{ __('Date of birth') }}</span>
                        <p class="rounded-lg bg-ink-50 px-3 py-2.5 text-sm text-ink-600">{{ $user->date_of_birth->format('F j, Y') }}</p>
                    </div>
                    <p class="text-xs text-ink-400 sm:col-span-2">{{ __('Country and date of birth are tied to identity verification. Contact support if they need correcting.') }}</p>

                    <div class="sm:col-span-2">
                        <button type="submit" class="btn-primary">{{ __('Save changes') }}</button>
                    </div>
                </form>
            </section>
        </div>

        <div class="space-y-6">
            {{-- Verification status --}}
            <section class="card p-6">
                <h2 class="text-base font-semibold text-ink-900">{{ __('Verification') }}</h2>
                <ul class="mt-4 space-y-3 text-sm">
                    <li class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-ink-700"><x-icon name="mail" class="size-4 text-ink-400" /> {{ __('Email') }}</span>
                        <x-status-badge :status="$user->hasVerifiedEmail() ? 'approved' : 'pending'">{{ $user->hasVerifiedEmail() ? __('Verified') : __('Unverified') }}</x-status-badge>
                    </li>
                    <li class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-ink-700"><x-icon name="phone" class="size-4 text-ink-400" /> {{ __('Phone') }}</span>
                        <x-status-badge :status="$user->hasVerifiedPhone() ? 'approved' : 'pending'">{{ $user->hasVerifiedPhone() ? __('Verified') : __('Unverified') }}</x-status-badge>
                    </li>
                    <li class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-ink-700"><x-icon name="badge" class="size-4 text-ink-400" /> {{ __('Identity (KYC)') }}</span>
                        <x-status-badge :status="$user->kycStatus()" />
                    </li>
                </ul>
            </section>

            @unless ($user->hasVerifiedPhone())
                <section class="card p-6" id="phone">
                    <h2 class="text-base font-semibold text-ink-900">{{ __('Verify your phone') }}</h2>
                    <p class="mt-1 text-sm text-ink-500">{{ __('We will text a 6-digit code to :phone.', ['phone' => $user->phone]) }}</p>

                    <form method="POST" action="{{ route('account.phone.send') }}" class="mt-4">
                        @csrf
                        <button type="submit" class="btn-secondary w-full">{{ session('status') === 'phone-code-sent' ? __('Send a new code') : __('Send code') }}</button>
                    </form>

                    <form method="POST" action="{{ route('account.phone.verify') }}" class="mt-4 space-y-3">
                        @csrf
                        <label for="code" class="form-label">{{ __('Verification code') }}</label>
                        <input id="code" name="code" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code" class="form-input tracking-[0.4em]">
                        @error('code') <p class="form-error">{{ $message }}</p> @enderror
                        <button type="submit" class="btn-primary w-full">{{ __('Verify phone') }}</button>
                    </form>
                </section>
            @endunless
        </div>
    </div>
</x-layouts.app>
