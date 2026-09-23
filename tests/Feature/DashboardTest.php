<?php

use App\Enums\TransactionType;
use App\Models\User;
use App\Services\Dashboard\AdminDashboardService;
use App\Services\Dashboard\UserDashboardService;

test('a new customer sees an empty but working dashboard', function () {
    $user = $this->customer();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Available balance')
        ->assertSee('USD 0.00')
        ->assertSee('Finish setting up your account')
        ->assertSee('No transactions yet');
});

test('dashboard figures are derived from the ledger', function () {
    $user = $this->customer();
    $this->fund($user, '1500');
    $this->fund($user, '500.25');

    $summary = app(UserDashboardService::class)->summary($user);

    expect($summary['available'])->toBe('2000.25000000')
        ->and($summary['total_deposited'])->toBe('2000.25000000')
        ->and($summary['total_withdrawn'])->toBe('0.00000000')
        ->and($summary['portfolio_value'])->toBe('2000.25000000')
        ->and($summary['realized_pnl'])->toBe('0.00000000')
        ->and($summary['recent_transactions'])->toHaveCount(2)
        ->and($summary['recent_transactions']->first()->type)->toBe(TransactionType::Deposit);

    $charts = app(UserDashboardService::class)->charts($user);
    expect(array_sum($charts['deposits']['values']))->toEqual(2000.25)
        ->and($charts['deposits']['labels'])->toHaveCount(30);

    $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('USD 2,000.25');
});

test('the transactions page lists and filters the customer\'s ledger', function () {
    $user = $this->customer();
    $this->fund($user, '12.34');

    $this->actingAs($user)->get(route('transactions.index'))->assertOk()->assertSee('USD 12.34');
    $this->actingAs($user)->get(route('transactions.index', ['type' => 'withdrawal']))->assertOk()->assertDontSee('USD 12.34');
});

test('the admin dashboard reports real totals', function () {
    $this->fund($this->customer(), '300');
    $this->fund($this->customer(), '200');
    User::factory()->staff()->create();

    $stats = app(AdminDashboardService::class)->stats();

    expect($stats['total_users'])->toBe(2)
        ->and($stats['total_deposits'])->toBe('500.00000000')
        ->and($stats['pending_withdrawals'])->toBe(0);

    $this->actingAs($this->staff())->get(route('admin.dashboard'))->assertOk()->assertSee('USD 500.00');
});

test('profile and security pages render', function () {
    $user = $this->customer();

    $this->actingAs($user)->get(route('account.profile'))->assertOk()->assertSee($user->email);
    $this->actingAs($user)->get(route('account.security'))->assertOk()->assertSee('Two-factor authentication');
});

test('legal pages render and unknown ones 404', function () {
    $this->get(route('legal.show', 'risk'))->assertOk()->assertSee('Risk Disclosure');
    $this->get(route('legal.show', 'nope'))->assertNotFound();
});

test('the demo banner is shown in sandbox mode', function () {
    config(['tradevault.demo_mode' => true]);
    $this->get('/')->assertSee('DEMO / SANDBOX');

    config(['tradevault.demo_mode' => false]);
    $this->get('/')->assertDontSee('DEMO / SANDBOX');
});
