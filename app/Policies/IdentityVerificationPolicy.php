<?php

namespace App\Policies;

use App\Models\IdentityVerification;
use App\Models\User;

class IdentityVerificationPolicy
{
    /**
     * Determine whether the user can view the verification list.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('verifications.view');
    }

    /**
     * Determine whether the user can view a specific verification.
     */
    public function view(User $user, IdentityVerification $verification): bool
    {
        return $user->hasPermission('verifications.view');
    }

    /**
     * Determine whether the user can create a verification.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('verifications.create');
    }

    /**
     * Determine whether the user can review a verification.
     */
    public function review(User $user, IdentityVerification $verification): bool
    {
        return $user->hasPermission('verifications.review');
    }
}
