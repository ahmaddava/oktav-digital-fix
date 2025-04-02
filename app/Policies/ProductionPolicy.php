<?php

// app/Policies/ProductionPolicy.php

namespace App\Policies;

use App\Models\Production;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Production $production)
    {
        return $user->can('production.view');
    }

    public function create(User $user)
    {
        return $user->can('production.create');
    }

    public function update(User $user, Production $production)
    {
        return $user->can('production.edit');
    }

    public function complete(User $user, Production $production)
    {
        return $user->can('production.complete');
    }
}