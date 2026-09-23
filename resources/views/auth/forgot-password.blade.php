<x-layouts.guest :title="__('Reset your password')">
    <x-slot:subtitle>{{ __('Enter your email and we will send you a reset link if an account exists.') }}</x-slot:subtitle>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <div>
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="form-input">
            @error('email') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="btn-primary w-full">{{ __('Email reset link') }}</button>
    </form>

    <p class="mt-6 text-center text-sm"><a href="{{ route('login') }}" class="font-medium text-brand-700 hover:text-brand-800">{{ __('Back to sign in') }}</a></p>
</x-layouts.guest>
