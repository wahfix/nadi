<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Determine whether the user can view the payment module.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payments.view');
    }

    /**
     * Determine whether the user can view a specific payment.
     */
    public function view(User $user, Payment $payment): bool
    {
        return $user->hasPermission('payments.view');
    }

    /**
     * Determine whether the user can record a new payment.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('payments.create');
    }

    /**
     * Determine whether the user can reverse an immutable payment.
     */
    public function reverse(User $user, Payment $payment): bool
    {
        return $user->hasPermission('payments.reverse');
    }

    /**
     * Determine whether the user can print a payment receipt.
     */
    public function receipt(User $user, Payment $payment): bool
    {
        return $user->hasPermission('payments.receipt');
    }
}
