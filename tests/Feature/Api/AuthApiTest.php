<?php

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\PersonalAccessToken;
use PragmaRX\Google2FA\Google2FA;

test('a customer can register through the API and receives a token', function () {
    $this->postJson(route('api.auth.register'), [
        'name' => 'Api Person',
        'email' => 'api@example.com',
        'phone' => '+254700000001',
        'country' => 'KE',
        'date_of_birth' => '1990-01-01',
        'password' => 'Str0ng!Passw0rd',
        'password_confirmation' => 'Str0ng!Passw0rd',
        'terms' => true,
        'privacy' => true,
        'device_name' => 'pest',
    ])->assertCreated()
        ->assertJsonPath('user.email', 'api@example.com')
        ->assertJsonPath('user.email_verified', false)
        ->assertJsonStructure(['token']);

    expect(User::where('email', 'api@example.com')->first()->hasRole('user'))->toBeTrue();
});

test('API registration validates like the web form', function () {
    $this->postJson(route('api.auth.register'), ['device_name' => 'pest'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'phone', 'country', 'date_of_birth', 'password', 'terms', 'privacy']);
});

test('a customer can log in, call authenticated endpoints and log out', function () {
    $user = $this->customer();

    $token = $this->postJson(route('api.auth.login'), [
        'email' => $user->email, 'password' => 'password', 'device_name' => 'pest',
    ])->assertOk()->json('token');

    $this->withToken($token)->getJson(route('api.user'))->assertOk()->assertJsonPath('data.id', $user->uuid);
    $this->withToken($token)->getJson(route('api.user.profile'))->assertOk()->assertJsonPath('data.country.code', 'KE');

    $this->withToken($token)->postJson(route('api.auth.logout'))->assertNoContent();
    expect(PersonalAccessToken::count())->toBe(0)
        ->and(AuditLog::where('action', 'auth.logout')->exists())->toBeTrue();
});

test('API login with bad credentials fails and is audited', function () {
    $user = $this->customer();

    $this->postJson(route('api.auth.login'), ['email' => $user->email, 'password' => 'nope', 'device_name' => 'pest'])
        ->assertUnprocessable();

    expect(AuditLog::where('action', 'auth.failed')->exists())->toBeTrue();
});

test('API login requires a valid second factor when 2FA is enabled', function () {
    $user = User::factory()->withTwoFactor()->customer()->create();
    $credentials = ['email' => $user->email, 'password' => 'password', 'device_name' => 'pest'];

    $this->postJson(route('api.auth.login'), $credentials)->assertUnprocessable()->assertJsonValidationErrors('code');
    $this->postJson(route('api.auth.login'), $credentials + ['code' => '000000'])->assertUnprocessable();

    $code = (new Google2FA)->getCurrentOtp('JBSWY3DPEHPK3PXP');

    $this->postJson(route('api.auth.login'), $credentials + ['code' => $code])->assertOk()->assertJsonStructure(['token']);
});

test('recovery codes work once for API login', function () {
    $user = User::factory()->withTwoFactor()->customer()->create();
    $credentials = ['email' => $user->email, 'password' => 'password', 'device_name' => 'pest', 'recovery_code' => 'recovery-code-1'];

    $this->postJson(route('api.auth.login'), $credentials)->assertOk();
    $this->postJson(route('api.auth.login'), $credentials)->assertUnprocessable();
});

test('suspended users cannot obtain or use tokens', function () {
    $user = $this->customer();
    $token = $user->createToken('pest')->plainTextToken;
    $user->forceFill(['status' => 'suspended'])->save();

    $this->postJson(route('api.auth.login'), ['email' => $user->email, 'password' => 'password', 'device_name' => 'pest'])
        ->assertUnprocessable();
    $this->withToken($token)->getJson(route('api.user'))->assertForbidden();
});

test('forgot password never reveals whether an account exists', function () {
    Notification::fake();
    $user = $this->customer();

    $known = $this->postJson(route('api.auth.forgot-password'), ['email' => $user->email])->assertOk()->json('message');
    $unknown = $this->postJson(route('api.auth.forgot-password'), ['email' => 'ghost@example.com'])->assertOk()->json('message');

    expect($known)->toBe($unknown);
    Notification::assertSentTo($user, ResetPassword::class);
});

test('profile can be updated through the API', function () {
    $user = $this->customer();

    $this->actingAs($user, 'sanctum')->putJson(route('api.user.profile.update'), [
        'name' => 'Renamed Person', 'email' => $user->email, 'phone' => $user->phone,
    ])->assertOk()->assertJsonPath('data.name', 'Renamed Person');
});

test('API auth endpoints are rate limited', function () {
    foreach (range(1, 5) as $ignored) {
        $this->postJson(route('api.auth.login'), ['email' => 'x@example.com', 'password' => 'x', 'device_name' => 'pest']);
    }

    $this->postJson(route('api.auth.login'), ['email' => 'x@example.com', 'password' => 'x', 'device_name' => 'pest'])
        ->assertTooManyRequests();
});
