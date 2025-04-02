<?php

// app/Policies/CustomerPolicy.php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Customer $customer)
    {
        return $user->can('customer.view');
    }

    public function create(User $user)
    {
        return $user->can('customer.create');
    }

    public function update(User $user, Customer $customer)
    {
        return $user->can('customer.edit');
    }

    public function delete(User $user, Customer $customer)
    {
        return $user->can('customer.delete');
    }
}
