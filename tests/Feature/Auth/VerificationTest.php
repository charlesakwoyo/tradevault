<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Sms\SmsGateway;
use Illuminate\Support\Facades\URL;

test('unverified users are sent to the email verification notice', function () {
    $user = User::factory()->unverified()->customer()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('verification.notice'));
});

test('email can be verified through the signed link', function () {
    $user = User::factory()->unverified()->customer()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $this->actingAs($user)->get($url)->assertRedirect(route('dashboard').'?verified=1');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue()
        ->and(AuditLog::where('action', 'user.email_verified')->exists())->toBeTrue();
});

test('changing email address requires verifying it again', function () {
    $user = $this->customer();

    $this->actingAs($user)->put(route('user-profile-information.update'), [
        'name' => $user->name,
        'email' => 'changed@example.com',
        'phone' => $user->phone,
    ])->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->email)->toBe('changed@example.com')
        ->and($user->hasVerifiedEmail())->toBeFalse()
        ->and(AuditLog::where('action', 'user.email_changed')->exists())->toBeTrue();
});

test('a phone number can be verified with a one-time code', function () {
    $sent = [];
    $this->app->instance(SmsGateway::class, new class($sent) implements SmsGateway
    {
        public function __construct(private array &$sent) {}

        public function send(string $to, string $message): void
        {
            $this->sent[] = compact('to', 'message');
        }
    });

    $user = $this->customer();

    $this->actingAs($user)->post(route('account.phone.send'))->assertSessionHas('status', 'phone-code-sent');

    expect($sent)->toHaveCount(1);
    preg_match('/\b(\d{6})\b/', $sent[0]['message'], $matches);

    $this->actingAs($user)->post(route('account.phone.verify'), ['code' => $matches[1] === '000000' ? '111111' : '000000'])
        ->assertSessionHasErrors('code');

    $this->actingAs($user)->post(route('account.phone.verify'), ['code' => $matches[1]])
        ->assertSessionHas('status', 'phone-verified');

    expect($user->fresh()->hasVerifiedPhone())->toBeTrue();
});

test('changing phone number resets phone verification', function () {
    $user = User::factory()->phoneVerified()->customer()->create();

    $this->actingAs($user)->put(route('user-profile-information.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'phone' => '+254799999999',
    ])->assertSessionHasNoErrors();

    expect($user->fresh()->hasVerifiedPhone())->toBeFalse();
});
