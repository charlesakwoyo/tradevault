<?php

namespace App\Http\Controllers\Api;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Resources\WalletResource;
use App\Http\Resources\WalletTransactionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * Read-only wallet endpoints. Always scoped to the authenticated user, so one
 * customer can never read another customer's wallet.
 */
class WalletController extends Controller
{
    public function show(Request $request): AnonymousResourceCollection
    {
        return WalletResource::collection($request->user()->wallets()->orderBy('currency')->orderBy('type')->get());
    }

    public function transactions(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'type' => ['nullable', Rule::enum(TransactionType::class)],
            'currency' => ['nullable', Rule::in(config('tradevault.currencies'))],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $transactions = $request->user()->walletTransactions()
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['currency'] ?? null, fn ($q, $currency) => $q->where('currency', $currency))
            ->latest()
            ->paginate($filters['per_page'] ?? 25);

        return WalletTransactionResource::collection($transactions);
    }
}
