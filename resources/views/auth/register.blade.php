<x-layouts.guest :title="__('Create your account')" wide>
    <x-slot:subtitle>{{ __('It takes a couple of minutes. You will verify your email and phone next.') }}</x-slot:subtitle>

    <form method="POST" action="{{ route('register.store') }}" class="grid grid-cols-1 gap-5 sm:grid-cols-2" x-data="{ password: '' }">
        @csrf

        <div class="sm:col-span-2">
            <label for="name" class="form-label">{{ __('Full legal name') }}</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name" class="form-input">
            <p class="mt-1 text-xs text-ink-400">{{ __('Must match your identity document for verification.') }}</p>
            @error('name') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="form-input">
            @error('email') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="phone" class="form-label">{{ __('Phone number') }}</label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required autocomplete="tel" placeholder="+254712345678" class="form-input">
            @error('phone') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="country" class="form-label">{{ __('Country of residence') }}</label>
            <select id="country" name="country" required class="form-input">
                <option value="">{{ __('Select…') }}</option>
                @foreach ($countries as $code => $name)
                    <option value="{{ $code }}" @selected(old('country') === $code)>{{ $name }}</option>
                @endforeach
            </select>
            @error('country') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="date_of_birth" class="form-label">{{ __('Date of birth') }}</label>
            <input id="date_of_birth" name="date_of_birth" type="date" value="{{ old('date_of_birth') }}" required max="{{ now()->subYears(config('tradevault.registration.minimum_age'))->toDateString() }}" class="form-input">
            @error('date_of_birth') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="form-label">{{ __('Password') }}</label>
            <input id="password" name="password" type="password" x-model="password" required autocomplete="new-password" class="form-input">
            @error('password') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="form-label">{{ __('Confirm password') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="form-input">
        </div>

        <ul class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs sm:col-span-2" aria-label="{{ __('Password requirements') }}">
            <li :class="password.length >= 12 ? 'text-emerald-600' : 'text-ink-400'">{{ __('At least 12 characters') }}</li>
            <li :class="/[a-z]/.test(password) && /[A-Z]/.test(password) ? 'text-emerald-600' : 'text-ink-400'">{{ __('Upper and lower case letters') }}</li>
            <li :class="/\d/.test(password) ? 'text-emerald-600' : 'text-ink-400'">{{ __('At least one number') }}</li>
            <li :class="/[^A-Za-z0-9]/.test(password) ? 'text-emerald-600' : 'text-ink-400'">{{ __('At least one symbol') }}</li>
        </ul>

        <div class="space-y-3 rounded-xl bg-ink-50 p-4 sm:col-span-2">
            <label class="flex items-start gap-3 text-sm text-ink-700">
                <input type="checkbox" name="terms" value="1" @checked(old('terms')) required class="mt-0.5 rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                <span>
                    {{ __('I have read and accept the') }}
                    <a href="{{ route('legal.show', 'terms') }}" target="_blank" class="font-medium text-brand-700 underline">{{ __('Terms and Conditions') }}</a>
                    {{ __('and the') }}
                    <a href="{{ route('legal.show', 'risk') }}" target="_blank" class="font-medium text-brand-700 underline">{{ __('Risk Disclosure') }}</a>.
                    {{ __('I understand that trading involves risk and I may lose money.') }}
                </span>
            </label>
            @error('terms') <p class="form-error">{{ $message }}</p> @enderror

            <label class="flex items-start gap-3 text-sm text-ink-700">
                <input type="checkbox" name="privacy" value="1" @checked(old('privacy')) required class="mt-0.5 rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                <span>
                    {{ __('I have read and accept the') }}
                    <a href="{{ route('legal.show', 'privacy') }}" target="_blank" class="font-medium text-brand-700 underline">{{ __('Privacy Policy') }}</a>.
                </span>
            </label>
            @error('privacy') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2">
            <button type="submit" class="btn-primary w-full">{{ __('Create account') }}</button>
            <p class="mt-4 text-center text-sm text-ink-500">
                {{ __('Already have an account?') }}
                <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:text-brand-800">{{ __('Sign in') }}</a>
            </p>
        </div>
    </form>
</x-layouts.guest>
