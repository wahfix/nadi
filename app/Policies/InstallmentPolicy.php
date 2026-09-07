<?php

namespace App\Policies;

use App\Models\Installment;
use App\Models\User;

class InstallmentPolicy
{
    /**
     * Determine whether the user can view the installment module.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('installments.view');
    }

    /**
     * Determine whether the user can view a specific installment.
     */
    public function view(User $user, Installment $installment): bool
    {
        return $user->hasPermission('installments.view');
    }
}
