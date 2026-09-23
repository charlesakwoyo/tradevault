<?php

use App\Enums\RoleName;
use App\Models\AuditLog;
use App\Models\User;

test('login screen can be rendered', function () {
    $this->get(route('login'))->assertOk();
});

test('customers are sent to their dashboard after login and the login is audited', function () {
    $user = $this->customer();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);

    $user->refresh();
    expect($user->last_login_at)->not->toBeNull()
        ->and(AuditLog::where('action', 'auth.login')->where('user_id', $user->id)->exists())->toBeTrue();
});

test('staff are sent to the back office after login', function () {
    $admin = $this->staff(RoleName::Support);

    $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard'));
});

test('login is case-insensitive on email', function () {
    $user = $this->customer(['email' => 'mixed@example.com']);

    $this->post(route('login.store'), ['email' => 'MIXED@Example.com', 'password' => 'password']);

    $this->assertAuthenticatedAs($user);
});

test('failed logins are rejected and audited', function () {
    $user = $this->customer();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();

    $log = AuditLog::where('action', 'auth.failed')->sole();
    expect($log->meta['known_account'])->toBeTrue()
        ->and($log->meta)->not->toHaveKey('password');
});

test('login is throttled after repeated failures', function () {
    $user = $this->customer();

    foreach (range(1, 5) as $ignored) {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong']);
    }

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertStatus(429);

    $this->assertGuest();
});

test('suspended users cannot log in and are told why', function () {
    $user = User::factory()->suspended()->customer()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => __('This account is suspended. Please contact support at :email.', ['email' => config('tradevault.support_email')])]);

    $this->assertGuest();
});

test('a user suspended mid-session is logged out on the next request', function () {
    $user = $this->customer();
    $this->actingAs($user);

    $user->forceFill(['status' => 'suspended'])->save();

    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('users with two-factor enabled are challenged', function () {
    $user = User::factory()->withTwoFactor()->customer()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('two-factor.login'));

    $this->assertGuest();
});

test('users can log out and the logout is audited', function () {
    $user = $this->customer();

    $this->actingAs($user)->post(route('logout'))->assertRedirect('/');

    $this->assertGuest();
    expect(AuditLog::where('action', 'auth.logout')->where('user_id', $user->id)->exists())->toBeTrue();
});

test('changing the password is audited', function () {
    $user = $this->customer();

    $this->actingAs($user)->put(route('user-password.update'), [
        'current_password' => 'password',
        'password' => 'N3w!SecurePassword',
        'password_confirmation' => 'N3w!SecurePassword',
    ])->assertRedirect()->assertSessionHasNoErrors('updatePassword');

    $log = AuditLog::where('action', 'user.password_changed')->sole();
    expect($log->user_id)->toBe($user->id);
});
