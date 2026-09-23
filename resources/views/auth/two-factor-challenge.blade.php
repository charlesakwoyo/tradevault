<x-layouts.guest :title="__('Two-factor authentication')">
    <div x-data="{ recovery: false }">
        <p class="text-sm text-ink-500" x-show="! recovery">{{ __('Enter the 6-digit code from your authenticator app.') }}</p>
        <p class="text-sm text-ink-500" x-cloak x-show="recovery">{{ __('Enter one of your emergency recovery codes.') }}</p>

        <form method="POST" action="{{ route('two-factor.login.store') }}" class="mt-5 space-y-5">
            @csrf
            <div x-show="! recovery">
                <label for="code" class="form-label">{{ __('Authentication code') }}</label>
                <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" autofocus x-bind:disabled="recovery" class="form-input tracking-[0.4em]">
            </div>
            <div x-cloak x-show="recovery">
                <label for="recovery_code" class="form-label">{{ __('Recovery code') }}</label>
                <input id="recovery_code" name="recovery_code" type="text" autocomplete="one-time-code" x-bind:disabled="! recovery" class="form-input">
            </div>
            @error('code') <p class="form-error">{{ $message }}</p> @enderror
            @error('recovery_code') <p class="form-error">{{ $message }}</p> @enderror

            <button type="submit" class="btn-primary w-full">{{ __('Verify') }}</button>
        </form>

        <button type="button" class="mt-4 w-full text-center text-sm font-medium text-brand-700 hover:text-brand-800" @click="recovery = ! recovery">
            <span x-show="! recovery">{{ __('Use a recovery code instead') }}</span>
            <span x-cloak x-show="recovery">{{ __('Use an authentication code') }}</span>
        </button>
    </div>
</x-layouts.guest>
