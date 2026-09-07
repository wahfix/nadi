<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    /**
     * Determine whether the user can view the customer list or a customer profile.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('customers.view');
    }

    /**
     * Determine whether the user can view a specific customer profile.
     */
    public function view(User $user, Customer $customer): bool
    {
        return $user->hasPermission('customers.view');
    }

    /**
     * Determine whether the user can register a new customer.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('customers.create');
    }

    /**
     * Determine whether the user can update a customer profile.
     */
    public function update(User $user, Customer $customer): bool
    {
        return $user->hasPermission('customers.edit');
    }
}
