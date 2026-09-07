<?php

namespace App\Policies;

use App\Models\Collateral;
use App\Models\User;

class CollateralPolicy
{
    /**
     * Determine whether the user can view the collateral list.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('collaterals.view');
    }

    /**
     * Determine whether the user can view a specific collateral.
     */
    public function view(User $user, Collateral $collateral): bool
    {
        return $user->hasPermission('collaterals.view');
    }

    /**
     * Determine whether the user can receive a collateral.
     */
    public function receive(User $user): bool
    {
        return $user->hasPermission('collaterals.receive');
    }

    /**
     * Determine whether the user can update custody status.
     */
    public function updateCustody(User $user, Collateral $collateral): bool
    {
        return $user->hasPermission('collaterals.update_custody');
    }
}
