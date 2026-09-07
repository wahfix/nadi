<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    /**
     * Determine whether the user can view the loan module.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('loans.view');
    }

    /**
     * Determine whether the user can view a specific loan.
     */
    public function view(User $user, Loan $loan): bool
    {
        return $user->hasPermission('loans.view');
    }

    /**
     * Determine whether the user can create a loan application.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('loans.create');
    }

    /**
     * Determine whether the user can edit a DRAFT loan application.
     */
    public function update(User $user, Loan $loan): bool
    {
        return $user->hasPermission('loans.edit') && $loan->status === Loan::STATUS_DRAFT;
    }

    /**
     * Determine whether the user can take a loan for review.
     */
    public function review(User $user, Loan $loan): bool
    {
        return $user->hasPermission('loans.review');
    }

    /**
     * Determine whether the user can approve or reject a loan.
     */
    public function approve(User $user, Loan $loan): bool
    {
        return $user->hasPermission('loans.approve');
    }

    /**
     * Determine whether the user can disburse an approved loan.
     */
    public function disburse(User $user, Loan $loan): bool
    {
        return $user->hasPermission('loans.disburse');
    }
}
