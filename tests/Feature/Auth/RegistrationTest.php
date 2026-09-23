<?php

use App\Enums\RoleName;
use App\Enums\WalletType;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

function registrationPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Amina Otieno',
        'email' => 'Amina@Example.com',
        'phone' => '+254 712 345 678',
        'country' => 'KE',
        'date_of_birth' => now()->subYears(30)->toDateString(),
        'password' => 'Str0ng!Passw0rd',
        'password_confirmation' => 'Str0ng!Passw0rd',
        'terms' => '1',
        'privacy' => '1',
    ], $overrides);
}

test('registration screen can be rendered', function () {
    $this->get(route('register'))->assertOk()->assertSee('Create your account');
});

test('a customer can register and gets a role, wallets and a verification email', function () {
    Notification::fake();

    $response = $this->post(route('register.store'), registrationPayload());

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();

    $user = User::sole();
    expect($user->email)->toBe('amina@example.com')
        ->and($user->phone)->toBe('+254712345678')
        ->and($user->hasRole(RoleName::User->value))->toBeTrue()
        ->and($user->terms_accepted_at)->not->toBeNull()
        ->and($user->privacy_accepted_at)->not->toBeNull()
        ->and($user->terms_version)->toBe(config('tradevault.legal.terms_version'))
        ->and($user->hasVerifiedEmail())->toBeFalse();

    $walletCount = count(config('tradevault.currencies')) * 3;
    expect($user->wallets()->count())->toBe($walletCount)
        ->and($user->wallets()->where('type', WalletType::Available)->sum('balance'))->toEqual(0);

    Notification::assertSentTo($user, VerifyEmail::class);
    expect(AuditLog::where('action', 'auth.registered')->where('auditable_id', $user->id)->exists())->toBeTrue();
});

test('terms and privacy acceptance are required', function () {
    $this->post(route('register.store'), registrationPayload(['terms' => null, 'privacy' => null]))
        ->assertSessionHasErrors(['terms', 'privacy']);

    $this->assertGuest();
});

test('duplicate email and phone numbers are rejected regardless of formatting', function () {
    User::factory()->create(['email' => 'amina@example.com', 'phone' => '+254712345678']);

    $this->post(route('register.store'), registrationPayload(['email' => 'AMINA@example.com', 'phone' => '0025 4712 345678']))
        ->assertSessionHasErrors(['email', 'phone']);

    expect(User::count())->toBe(1);
});

test('weak passwords are rejected', function (string $password) {
    $this->post(route('register.store'), registrationPayload(['password' => $password, 'password_confirmation' => $password]))
        ->assertSessionHasErrors('password');
})->with([
    'too short' => 'Sh0rt!pw',
    'no symbol' => 'NoSymbolPassw0rd',
    'no number' => 'NoNumber!Password',
    'no uppercase' => 'nouppercase!passw0rd',
]);

test('under-age applicants cannot register', function () {
    $this->post(route('register.store'), registrationPayload(['date_of_birth' => now()->subYears(17)->toDateString()]))
        ->assertSessionHasErrors('date_of_birth');
});

test('residents of blocked countries cannot register', function () {
    config(['tradevault.geo.blocked_countries' => ['KP']]);

    $this->post(route('register.store'), registrationPayload(['country' => 'KP']))
        ->assertSessionHasErrors('country');
});

test('invalid phone numbers are rejected', function () {
    $this->post(route('register.store'), registrationPayload(['phone' => '12345']))
        ->assertSessionHasErrors('phone');
});

test('registration can be closed by configuration', function () {
    config(['tradevault.registration.enabled' => false]);

    $this->post(route('register.store'), registrationPayload())->assertSessionHasErrors('email');
    expect(User::count())->toBe(0);
});
