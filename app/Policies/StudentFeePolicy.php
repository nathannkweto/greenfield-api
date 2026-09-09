<?php declare(strict_types=1);

namespace App\Policies;

use App\Models\StudentFee;
use App\Models\User;

class StudentFeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin', 'finance_manager', 'accountant', 'cashier');
    }

    public function view(User $user, StudentFee $studentFee): bool
    {
        if ($user->hasRole('admin', 'finance_manager', 'accountant', 'cashier')) {
            return true;
        }

        // Students can view their own assigned fees
        return $user->student && $user->student->id === $studentFee->student_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin', 'finance_manager', 'accountant');
    }

    public function update(User $user, StudentFee $studentFee): bool
    {
        return $user->hasRole('admin', 'finance_manager');
    }

    public function delete(User $user, StudentFee $studentFee): bool
    {
        return $user->hasRole('admin');
    }
}
