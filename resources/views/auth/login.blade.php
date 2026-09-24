<x-layouts.guest :title="__('Sign in')">
    <x-slot:subtitle>{{ __('Welcome back. Sign in to your account.') }}</x-slot:subtitle>

    @include('auth.partials.google-button')

    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="form-input">
            @error('email') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="form-label">{{ __('Password') }}</label>
                <a href="{{ route('password.request') }}" class="mb-1.5 text-sm font-medium text-brand-700 hover:text-brand-800">{{ __('Forgot password?') }}</a>
            </div>
            <input id="password" name="password" type="password" required autocomplete="current-password" class="form-input">
            @error('password') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-ink-600">
            <input type="checkbox" name="remember" class="rounded border-ink-300 text-brand-600 focus:ring-brand-500">
            {{ __('Keep me signed in on this device') }}
        </label>

        <button type="submit" class="btn-primary w-full">{{ __('Sign in') }}</button>
    </form>

    @if (Route::has('register'))
        <p class="mt-6 text-center text-sm text-ink-500">
            {{ __('New to :app?', ['app' => config('app.name')]) }}
            <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:text-brand-800">{{ __('Create an account') }}</a>
        </p>
    @endif
</x-layouts.guest>
