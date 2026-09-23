<?php

use App\Enums\RoleName;
use App\Enums\SystemAccount;
use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Exceptions\Wallet\InsufficientFundsException;
use App\Exceptions\Wallet\LedgerException;
use App\Models\AuditLog;
use App\Models\LedgerEntry;
use App\Models\WalletTransaction;
use App\Services\Wallet\LedgerService;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->ledger = app(LedgerService::class);
    $this->wallets = app(WalletService::class);
    $this->currency = config('tradevault.base_currency');
});

test('a posting writes balanced entries and updates cached balances', function () {
    $user = $this->customer();

    $tx = $this->fund($user, '250.50');

    $available = $this->wallets->userWallet($user, WalletType::Available, $this->currency);
    $clearing = $this->wallets->systemWallet(SystemAccount::DepositsClearing, $this->currency);

    expect($available->balance)->toBe('250.50000000')
        ->and($clearing->balance)->toBe('-250.50000000')
        ->and($tx->entries()->count())->toBe(2)
        ->and($tx->entries()->sum('amount'))->toEqual(0)
        ->and($this->ledger->derivedBalance($available))->toBe('250.50000000');
});

test('balances are exact to 8 decimal places', function () {
    $user = $this->customer();

    foreach (range(1, 10) as $ignored) {
        $this->fund($user, '0.1');
    }

    expect($this->wallets->userWallet($user, WalletType::Available, $this->currency)->balance)->toBe('1.00000000');
});

test('a customer wallet can never go negative', function () {
    $user = $this->customer();
    $this->fund($user, '100');

    $available = $this->wallets->userWallet($user, WalletType::Available, $this->currency);
    $clearing = $this->wallets->systemWallet(SystemAccount::WithdrawalsClearing, $this->currency);

    expect(fn () => $this->ledger->post(
        TransactionType::Withdrawal, $user, '100.01', $this->currency,
        [[$available, '-100.01'], [$clearing, '100.01']],
        'TEST-OVERDRAW', 'Overdraw attempt',
    ))->toThrow(InsufficientFundsException::class);

    expect($available->fresh()->balance)->toBe('100.00000000')
        ->and(WalletTransaction::where('reference', 'TEST-OVERDRAW')->exists())->toBeFalse();
});

test('unbalanced postings are refused', function () {
    $user = $this->customer();
    $available = $this->wallets->userWallet($user, WalletType::Available, $this->currency);
    $clearing = $this->wallets->systemWallet(SystemAccount::DepositsClearing, $this->currency);

    expect(fn () => $this->ledger->post(
        TransactionType::Deposit, $user, '10', $this->currency,
        [[$available, '10'], [$clearing, '-9.99']],
        'TEST-UNBALANCED', 'Unbalanced',
    ))->toThrow(LedgerException::class);

    expect(LedgerEntry::count())->toBe(0);
});

test('legs in a different currency are refused', function () {
    $user = $this->customer();
    $usd = $this->wallets->userWallet($user, WalletType::Available, 'USD');
    $kes = $this->wallets->systemWallet(SystemAccount::DepositsClearing, 'KES');

    expect(fn () => $this->ledger->post(
        TransactionType::Deposit, $user, '10', 'USD', [[$usd, '10'], [$kes, '-10']], 'TEST-FX', 'Mixed currency',
    ))->toThrow(LedgerException::class);
});

test('replaying the same reference does not move money twice', function () {
    $user = $this->customer();
    $available = $this->wallets->userWallet($user, WalletType::Available, $this->currency);
    $clearing = $this->wallets->systemWallet(SystemAccount::DepositsClearing, $this->currency);
    $post = fn () => $this->ledger->post(
        TransactionType::Deposit, $user, '75', $this->currency,
        [[$available, '75'], [$clearing, '-75']],
        'PROVIDER-REF-123', 'Deposit',
    );

    $first = $post();
    $second = $post();

    expect($second->is($first))->toBeTrue()
        ->and($available->fresh()->balance)->toBe('75.00000000')
        ->and(WalletTransaction::count())->toBe(1);
});

test('ledger entries are immutable', function () {
    $user = $this->customer();
    $this->fund($user, '10');
    $entry = LedgerEntry::first();

    expect(fn () => $entry->forceFill(['amount' => '1000'])->save())->toThrow(LogicException::class)
        ->and(fn () => $entry->delete())->toThrow(LogicException::class);
});

test('adjustments require a non-zero amount and a descriptive reason', function (string $amount, string $reason) {
    $admin = $this->staff(RoleName::Finance);
    $user = $this->customer();

    expect(fn () => $this->wallets->adjust($user, $this->currency, $amount, $reason, $admin))
        ->toThrow(InvalidArgumentException::class);

    expect(WalletTransaction::count())->toBe(0);
})->with([
    'zero amount' => ['0', 'A perfectly good reason for this'],
    'vague reason' => ['10', 'fix'],
]);

test('admin balance adjustment creates an audit record with reason, amount, admin and timestamp', function () {
    $admin = $this->staff(RoleName::Finance);
    $user = $this->customer();
    $this->fund($user, '50');

    $tx = $this->wallets->adjust($user, $this->currency, '-20', 'Reversal of duplicated manual credit #4411', $admin);

    expect($tx->type)->toBe(TransactionType::Adjustment)
        ->and($tx->created_by)->toBe($admin->id)
        ->and($tx->amount)->toBe('20.00000000')
        ->and($this->wallets->userWallet($user, WalletType::Available, $this->currency)->balance)->toBe('30.00000000');

    $audit = AuditLog::where('action', 'wallet.adjusted')->sole();
    expect($audit->user_id)->toBe($admin->id)
        ->and($audit->meta['reason'])->toBe('Reversal of duplicated manual credit #4411')
        ->and($audit->meta['amount'])->toBe('-20.00000000')
        ->and($audit->old_values['balance'])->toBe('50.00000000')
        ->and($audit->new_values['balance'])->toBe('30.00000000')
        ->and($audit->created_at)->not->toBeNull();
});

test('an adjustment cannot push a customer below zero', function () {
    $admin = $this->staff();
    $user = $this->customer();

    expect(fn () => $this->wallets->adjust($user, $this->currency, '-1', 'Attempted debit of empty wallet', $admin))
        ->toThrow(InsufficientFundsException::class);

    expect(AuditLog::where('action', 'wallet.adjusted')->exists())->toBeFalse();
});

test('audit logs are append-only', function () {
    $user = $this->customer();
    $this->actingAs($user)->post(route('logout'));
    $log = AuditLog::first();

    expect(fn () => $log->forceFill(['action' => 'tampered'])->save())->toThrow(LogicException::class)
        ->and(fn () => $log->delete())->toThrow(LogicException::class);
});

test('ledger:verify passes for a consistent ledger and fails when a balance is tampered with', function () {
    $user = $this->customer();
    $this->fund($user, '42');

    $this->artisan('ledger:verify')->assertSuccessful();

    // Simulate an out-of-band UPDATE bypassing LedgerService.
    $wallet = $this->wallets->userWallet($user, WalletType::Available, $this->currency);
    DB::table('wallets')->where('id', $wallet->id)->update(['balance' => '1000']);

    $this->artisan('ledger:verify')->assertFailed();
});
