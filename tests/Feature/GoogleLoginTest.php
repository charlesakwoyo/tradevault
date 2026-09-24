<?php

use App\Enums\RoleName;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;

beforeEach(function () {
    config(['services.google.client_id' => 'test-client', 'services.google.client_secret' => 'test-secret']);
});

function fakeGoogleUser(string $email = 'amina@example.com', string $id = 'google-123', bool $verified = true): void
{
    $googleUser = (new GoogleUser)
        ->setRaw(['email_verified' => $verified])
        ->map(['id' => $id, 'name' => 'Amina Otieno', 'email' => $email]);

    Socialite::shouldReceive('driver->user')->andReturn($googleUser);
}

test('the google button shows only when google is configured', function () {
    $this->get(route('login'))->assertSee('Continue with Google');

    config(['services.google.client_secret' => null]);
    $this->get(route('login'))->assertDontSee('Continue with Google');
    $this->get(route('auth.google.redirect'))->assertNotFound();
});

test('an existing verified customer signs in with google and the account gets linked', function () {
    $user = $this->customer(['email' => 'amina@example.com']);
    fakeGoogleUser();

    $this->get(route('auth.google.callback'))->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->google_id)->toBe('google-123');
});

test('google never links to an account whose email is unverified', function () {
    $user = User::factory()->unverified()->customer()->create(['email' => 'amina@example.com']);
    fakeGoogleUser();

    $this->get(route('auth.google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors('email');

    $this->assertGuest();
    expect($user->fresh()->google_id)->toBeNull();
});

test('an unverified google email is refused', function () {
    fakeGoogleUser(verified: false);

    $this->get(route('auth.google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('an account linked to another google account is refused', function () {
    $this->customer(['email' => 'amina@example.com', 'google_id' => 'someone-else']);
    fakeGoogleUser();

    $this->get(route('auth.google.callback'))->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('suspended customers cannot sign in with google', function () {
    User::factory()->suspended()->customer()->create(['email' => 'amina@example.com']);
    fakeGoogleUser();

    $this->get(route('auth.google.callback'))->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('customers with two-factor enabled still get the 2FA challenge', function () {
    $user = User::factory()->withTwoFactor()->customer()->create(['email' => 'amina@example.com']);
    fakeGoogleUser();

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('two-factor.login'))
        ->assertSessionHas('login.id', $user->id);

    $this->assertGuest();
});

test('a new google user finishes sign-up with the required details', function () {
    fakeGoogleUser();

    $this->get(route('auth.google.callback'))->assertRedirect(route('auth.google.complete'));
    $this->get(route('auth.google.complete'))->assertOk()->assertSee('amina@example.com');

    $this->post(route('auth.google.complete.store'), [
        'name' => 'Amina Otieno',
        'phone' => '+254 712 345 678',
        'country' => 'KE',
        'date_of_birth' => now()->subYears(30)->toDateString(),
        'terms' => '1',
        'privacy' => '1',
    ])->assertRedirect(route('dashboard'));

    $user = User::sole();
    $this->assertAuthenticatedAs($user);
    expect($user->google_id)->toBe('google-123')
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->phone)->toBe('+254712345678')
        ->and($user->terms_accepted_at)->not->toBeNull()
        ->and($user->hasRole(RoleName::User->value))->toBeTrue();
});

test('google sign-up still enforces the minimum age and consent', function () {
    fakeGoogleUser();
    $this->get(route('auth.google.callback'));

    $this->post(route('auth.google.complete.store'), [
        'name' => 'Amina Otieno',
        'phone' => '+254712345678',
        'country' => 'KE',
        'date_of_birth' => now()->subYears(15)->toDateString(),
    ])->assertSessionHasErrors(['date_of_birth', 'terms', 'privacy']);

    expect(User::count())->toBe(0);
});

test('the sign-up form cannot be used without a google profile in the session', function () {
    $this->get(route('auth.google.complete'))->assertRedirect(route('login'));
    $this->post(route('auth.google.complete.store'), ['name' => 'X'])->assertRedirect(route('login'));
    expect(User::count())->toBe(0);
});
