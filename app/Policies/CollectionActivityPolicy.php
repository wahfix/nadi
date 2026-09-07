<?php

namespace App\Policies;

use App\Models\CollectionActivity;
use App\Models\User;

class CollectionActivityPolicy
{
    /**
     * Determine whether the user can view any collection activities.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('collections.view');
    }

    /**
     * Determine whether the user can view a collection activity.
     */
    public function view(User $user, CollectionActivity $activity): bool
    {
        return $user->hasPermission('collections.view');
    }

    /**
     * Determine whether the user can record a collection activity.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('collections.create');
    }
}
