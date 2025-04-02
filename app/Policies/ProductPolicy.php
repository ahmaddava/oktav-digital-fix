<?php
// app/Policies/ProductPolicy.php

namespace App\Policies;

use App\Models\Product;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\User;

class ProductPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Product $product)
    {
        return $user->can('product.view');
    }

    public function create(User $user)
    {
        return $user->can('product.create');
    }

    public function update(User $user, Product $product)
    {
        return $user->can('product.edit');
    }

    public function delete(User $user, Product $product)
    {
        return $user->can('product.delete');
    }
}
