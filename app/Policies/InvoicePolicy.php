<?php
// app/Policies/InvoicePolicy.php

namespace App\Policies;

use App\Models\Invoice;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\User;

class InvoicePolicy
{
    use HandlesAuthorization;

    public function view(User $user, Invoice $invoice)
    {
        return $user->can('invoice.view');
    }

    public function create(User $user)
    {
        return $user->can('invoice.create');
    }

    public function update(User $user, Invoice $invoice)
    {
        return $user->can('invoice.edit');
    }

    public function delete(User $user, Invoice $invoice)
    {
        return $user->can('invoice.delete');
    }
}
