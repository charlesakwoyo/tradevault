<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wallet;

class WalletPolicy
{
    public function view(User $user, Wallet $wallet): bool
    {
        return ($wallet->user_id !== null && $wallet->user_id === $user->id)
            || $user->can('wallets.view');
    }

    /** Manual adjustments are staff-only and always audited (see WalletService::adjust). */
    public function adjust(User $user, Wallet $wallet): bool
    {
        return $user->can('wallets.adjust')
            && ! $wallet->isSystem()
            && $wallet->user_id !== $user->id;
    }
}
