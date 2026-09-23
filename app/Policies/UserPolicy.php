<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('users.view');
    }

    public function view(User $actor, User $user): bool
    {
        return $actor->is($user) || $actor->can('users.view');
    }

    /** Staff cannot suspend themselves or another administrator. */
    public function suspend(User $actor, User $user): bool
    {
        return $actor->can('users.manage')
            && ! $actor->is($user)
            && ! $user->hasRole('admin');
    }

    public function resetSecurity(User $actor, User $user): bool
    {
        return $actor->can('users.reset-security') && ! $actor->is($user);
    }
}
