<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->middleware('throttle:api-auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('api.auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('api.auth.login');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('api.auth.forgot-password');
});

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');

    Route::get('/user', [UserController::class, 'show'])->name('api.user');
    Route::get('/user/profile', [UserController::class, 'profile'])->name('api.user.profile');
    Route::put('/user/profile', [UserController::class, 'update'])->name('api.user.profile.update');

    Route::middleware(['verified', 'throttle:api'])->group(function () {
        Route::get('/wallet', [WalletController::class, 'show'])->name('api.wallet');
        Route::get('/wallet/transactions', [WalletController::class, 'transactions'])->name('api.wallet.transactions');
    });
});
