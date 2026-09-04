<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin', 'student');
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $user->hasRole('admin')
            || $user->students()->where('id', $transaction->student_id)->exists();
    }
}
