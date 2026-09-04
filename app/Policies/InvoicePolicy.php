<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin', 'student');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('admin')
            || $user->students()->where('id', $invoice->student_id)->exists();
    }
}
