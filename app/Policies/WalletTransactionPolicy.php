<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WalletTransaction;

class WalletTransactionPolicy
{
    public function view(User $user, WalletTransaction $transaction): bool
    {
        return ($transaction->user_id !== null && $transaction->user_id === $user->id)
            || $user->can('transactions.view');
    }
}
