<x-layouts.guest :title="__('Finish creating your account')" wide>
    <x-slot:subtitle>{{ __('You are signing up with Google as :email. We need a few more details that the platform requires by law.', ['email' => $pending['email']]) }}</x-slot:subtitle>

    <form method="POST" action="{{ route('auth.google.complete.store') }}" class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        @csrf

        @error('email') <p class="form-error sm:col-span-2">{{ $message }}</p> @enderror

        <div class="sm:col-span-2">
            <label for="name" class="form-label">{{ __('Full legal name') }}</label>
            <input id="name" name="name" type="text" value="{{ old('name', $pending['name']) }}" required autofocus autocomplete="name" class="form-input">
            <p class="mt-1 text-xs text-ink-400">{{ __('Must match your identity document for verification.') }}</p>
            @error('name') <p class="form-error">{{ $message }}</p> @enderror
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

        <div class="sm:col-span-2">
            <label for="date_of_birth" class="form-label">{{ __('Date of birth') }}</label>
            <input id="date_of_birth" name="date_of_birth" type="date" value="{{ old('date_of_birth') }}" required max="{{ now()->subYears(config('tradevault.registration.minimum_age'))->toDateString() }}" class="form-input sm:w-1/2">
            @error('date_of_birth') <p class="form-error">{{ $message }}</p> @enderror
        </div>

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
        </div>
    </form>
</x-layouts.guest>
