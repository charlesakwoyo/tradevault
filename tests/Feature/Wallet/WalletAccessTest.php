<?php

use App\Enums\RoleName;
use App\Enums\WalletType;
use App\Services\Wallet\WalletService;
use Laravel\Sanctum\Sanctum;

test('a customer cannot view another customer\'s transaction', function () {
    $owner = $this->customer();
    $intruder = $this->customer();
    $tx = $this->fund($owner, '100');

    $this->actingAs($intruder)->get(route('transactions.show', $tx))->assertForbidden();
    $this->actingAs($owner)->get(route('transactions.show', $tx))->assertOk()->assertSee($tx->reference);
});

test('the transaction detail hides system counter-account legs from customers', function () {
    $owner = $this->customer();
    $tx = $this->fund($owner, '100');

    $this->actingAs($owner)->get(route('transactions.show', $tx))
        ->assertOk()
        ->assertSee('Available Balance')
        ->assertDontSee('System');
});

test('the wallet API only ever returns the caller\'s own wallets', function () {
    $owner = $this->customer();
    $other = $this->customer();
    $this->fund($owner, '10');
    $this->fund($other, '999');

    Sanctum::actingAs($owner);

    $response = $this->getJson(route('api.wallet'))->assertOk();
    $ids = collect($response->json('data'))->pluck('id');

    expect($ids)->toHaveCount($owner->wallets()->count())
        ->and($ids->intersect($other->wallets()->pluck('uuid')))->toBeEmpty();

    $this->getJson(route('api.wallet.transactions'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.amount', '10.00000000');
});

test('wallet policy: owners and wallet staff may view, others may not', function () {
    $owner = $this->customer();
    $wallet = app(WalletService::class)->userWallet($owner, WalletType::Available, 'USD');

    expect($owner->can('view', $wallet))->toBeTrue()
        ->and($this->customer()->can('view', $wallet))->toBeFalse()
        ->and($this->staff(RoleName::Finance)->can('view', $wallet))->toBeTrue()
        ->and($this->staff(RoleName::Trading)->can('view', $wallet))->toBeFalse();
});

test('only authorized staff may adjust a wallet, and never their own', function () {
    $finance = $this->customer();
    $finance->assignRole(RoleName::Finance->value);
    $customerWallet = app(WalletService::class)->userWallet($this->customer(), WalletType::Available, 'USD');
    $ownWallet = app(WalletService::class)->userWallet($finance, WalletType::Available, 'USD');

    expect($finance->can('adjust', $customerWallet))->toBeTrue()
        ->and($finance->can('adjust', $ownWallet))->toBeFalse()
        ->and($this->staff(RoleName::Support)->can('adjust', $customerWallet))->toBeFalse();
});
