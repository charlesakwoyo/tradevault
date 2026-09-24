<?php

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('the owner can create an admin account from the console', function () {
    $this->artisan('app:create-admin', ['email' => 'Owner@Example.com'])
        ->expectsQuestion('Full name', 'Charles Owner')
        ->expectsQuestion('Phone number (international format)', '+254 712 345 678')
        ->expectsQuestion('Country code (2 letters)', 'ke')
        ->expectsQuestion('Date of birth (YYYY-MM-DD)', '1990-05-01')
        ->expectsQuestion('Password', 'Str0ng!Passw0rd')
        ->expectsQuestion('Confirm password', 'Str0ng!Passw0rd')
        ->assertSuccessful();

    $admin = User::sole();
    expect($admin->email)->toBe('owner@example.com')
        ->and($admin->phone)->toBe('+254712345678')
        ->and($admin->country)->toBe('KE')
        ->and($admin->hasVerifiedEmail())->toBeTrue()
        ->and($admin->hasRole(RoleName::Admin->value))->toBeTrue()
        ->and(Hash::check('Str0ng!Passw0rd', $admin->password))->toBeTrue();
});

test('mismatched passwords create nothing', function () {
    $this->artisan('app:create-admin', ['email' => 'owner@example.com'])
        ->expectsQuestion('Full name', 'Charles Owner')
        ->expectsQuestion('Phone number (international format)', '+254712345678')
        ->expectsQuestion('Country code (2 letters)', 'KE')
        ->expectsQuestion('Date of birth (YYYY-MM-DD)', '1990-05-01')
        ->expectsQuestion('Password', 'Str0ng!Passw0rd')
        ->expectsQuestion('Confirm password', 'Different!Passw0rd')
        ->assertFailed();

    expect(User::count())->toBe(0);
});

test('an existing account can be promoted to admin', function () {
    $user = $this->customer(['email' => 'owner@example.com']);

    $this->artisan('app:create-admin', ['email' => 'owner@example.com'])
        ->expectsConfirmation('An account for owner@example.com already exists. Make it an admin?', 'yes')
        ->assertSuccessful();

    expect($user->fresh()->hasRole(RoleName::Admin->value))->toBeTrue();
});
