<x-layouts.guest :title="__('Confirm your password')">
    <x-slot:subtitle>{{ __('For your security, please confirm your password to continue.') }}</x-slot:subtitle>

    <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-5">
        @csrf
        <div>
            <label for="password" class="form-label">{{ __('Password') }}</label>
            <input id="password" name="password" type="password" required autofocus autocomplete="current-password" class="form-input">
            @error('password') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="btn-primary w-full">{{ __('Confirm') }}</button>
    </form>
</x-layouts.guest>
