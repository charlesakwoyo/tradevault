<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Account\PhoneVerificationController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\GoogleLoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\TransactionController;
use App\Http\Middleware\RequireTwoFactorForStaff;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/legal/{document}', [LegalController::class, 'show'])->name('legal.show');

Route::middleware(['guest', 'throttle:auth-forms'])->prefix('auth/google')->name('auth.google.')->group(function () {
    Route::get('/redirect', [GoogleLoginController::class, 'redirect'])->name('redirect');
    Route::get('/callback', [GoogleLoginController::class, 'callback'])->name('callback');
    Route::get('/complete', [GoogleLoginController::class, 'create'])->name('complete');
    Route::post('/complete', [GoogleLoginController::class, 'store'])->name('complete.store');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/account/profile', [AccountController::class, 'profile'])->name('account.profile');
    Route::get('/account/security', [AccountController::class, 'security'])->name('account.security');

    Route::post('/account/phone/code', [PhoneVerificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('account.phone.send');
    Route::post('/account/phone/verify', [PhoneVerificationController::class, 'update'])
        ->middleware('throttle:10,1')
        ->name('account.phone.verify');
});

/*
 | Customer area
 */
Route::middleware(['auth', 'active', 'verified', 'customer'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/markets', [MarketController::class, 'index'])->name('markets.index');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
});

/*
 | Back office — every route additionally checks a specific permission.
 */
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'active', 'verified', 'permission:admin.access', RequireTwoFactorForStaff::class])
    ->group(function () {
        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [Admin\UserController::class, 'show'])->name('users.show');
    });
