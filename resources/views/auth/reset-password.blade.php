<x-layouts.guest :title="__('Choose a new password')">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required autocomplete="username" class="form-input">
            @error('email') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password" class="form-label">{{ __('New password') }}</label>
            <input id="password" name="password" type="password" required autofocus autocomplete="new-password" class="form-input">
            <p class="mt-1 text-xs text-ink-400">{{ __('At least 12 characters with upper and lower case letters, a number and a symbol.') }}</p>
            @error('password') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password_confirmation" class="form-label">{{ __('Confirm new password') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="form-input">
        </div>
        <button type="submit" class="btn-primary w-full">{{ __('Reset password') }}</button>
    </form>
</x-layouts.guest>
