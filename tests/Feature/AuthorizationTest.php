<?php

use App\Enums\RoleName;
use App\Support\RolePermissions;

test('customers cannot access the back office', function () {
    $this->actingAs($this->customer())->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs($this->customer())->get(route('admin.users.index'))->assertForbidden();
});

test('guests are redirected to login', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

test('every staff role can open the back-office dashboard', function (RoleName $role) {
    $this->actingAs($this->staff($role))->get(route('admin.dashboard'))->assertOk();
})->with([RoleName::Admin, RoleName::Support, RoleName::Finance, RoleName::Trading]);

test('staff without users.view cannot list users', function () {
    // The trading manager role has users.view, so strip it to prove the per-route permission is enforced.
    $staff = $this->staff(RoleName::Trading);
    $staff->roles->first()->revokePermissionTo('users.view');

    $this->actingAs($staff->fresh())->get(route('admin.users.index'))->assertForbidden();
});

test('staff are redirected away from the customer area', function () {
    $this->actingAs($this->staff(RoleName::Finance))->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
});

test('staff must enable 2FA before using the back office when required', function () {
    config(['security.require_staff_2fa' => true]);

    $this->actingAs($this->staff())->get(route('admin.dashboard'))->assertRedirect(route('account.security'));
});

test('role permissions match the documented matrix', function (RoleName $role, array $allowed, array $denied) {
    $user = $this->staff($role);

    foreach ($allowed as $permission) {
        expect($user->can($permission))->toBeTrue("{$role->value} should have {$permission}");
    }
    foreach ($denied as $permission) {
        expect($user->can($permission))->toBeFalse("{$role->value} should not have {$permission}");
    }
})->with([
    'support' => [RoleName::Support, ['kyc.review', 'support.respond'], ['wallets.adjust', 'withdrawals.review', 'markets.manage', 'settings.manage']],
    'finance' => [RoleName::Finance, ['withdrawals.review', 'wallets.adjust', 'reports.view'], ['markets.manage', 'kyc.review', 'settings.manage']],
    'trading' => [RoleName::Trading, ['markets.manage', 'orders.view'], ['withdrawals.review', 'wallets.adjust', 'kyc.review']],
]);

test('admins hold every permission', function () {
    $admin = $this->staff(RoleName::Admin);

    foreach (array_keys(RolePermissions::PERMISSIONS) as $permission) {
        expect($admin->can($permission))->toBeTrue();
    }
});

test('customers hold no back-office permissions', function () {
    $customer = $this->customer();

    foreach (array_keys(RolePermissions::PERMISSIONS) as $permission) {
        expect($customer->can($permission))->toBeFalse();
    }
});

test('admin user pages render with filters', function () {
    $admin = $this->staff();
    $customer = $this->customer(['name' => 'Findable Person']);

    $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'Findable']))
        ->assertOk()->assertSee('Findable Person');

    $this->actingAs($admin)->get(route('admin.users.show', $customer))
        ->assertOk()->assertSee($customer->email);
});
