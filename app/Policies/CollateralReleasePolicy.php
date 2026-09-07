<?php

namespace App\Policies;

use App\Models\CollateralRelease;
use App\Models\User;

class CollateralReleasePolicy
{
    /**
     * Determine whether the user can view the release list.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('releases.view');
    }

    /**
     * Determine whether the user can view a specific release.
     */
    public function view(User $user, CollateralRelease $release): bool
    {
        return $user->hasPermission('releases.view');
    }

    /**
     * Determine whether the user can execute a release.
     */
    public function execute(User $user): bool
    {
        return $user->hasPermission('releases.execute');
    }
}
