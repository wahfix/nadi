<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view the user management module.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.view');
    }

    /**
     * Determine whether the user can create a new system user.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('users.manage');
    }
}
