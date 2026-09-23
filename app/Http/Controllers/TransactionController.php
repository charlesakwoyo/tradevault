<?php

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'type' => ['nullable', Rule::enum(TransactionType::class)],
            'currency' => ['nullable', Rule::in(config('tradevault.currencies'))],
        ]);

        $transactions = $request->user()->walletTransactions()
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['currency'] ?? null, fn ($q, $currency) => $q->where('currency', $currency))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('transactions.index', [
            'transactions' => $transactions,
            'filters' => $filters,
        ]);
    }

    /**
     * Customers see only the legs that touched their own wallets; system
     * counter-account legs are back-office information.
     */
    public function show(Request $request, WalletTransaction $transaction): View
    {
        $this->authorize('view', $transaction);

        $transaction->load(['entries' => fn ($q) => $q->whereHas('wallet', fn ($w) => $w->where('user_id', $request->user()->id)), 'entries.wallet']);

        return view('transactions.show', ['transaction' => $transaction]);
    }
}
