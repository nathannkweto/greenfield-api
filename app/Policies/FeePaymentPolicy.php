<?php declare(strict_types=1);

namespace App\Policies;

use App\Models\FeePayment;
use App\Models\User;

class FeePaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin', 'finance_manager', 'accountant', 'cashier');
    }

    public function view(User $user, FeePayment $feePayment): bool
    {
        if ($user->hasRole('admin', 'finance_manager', 'accountant', 'cashier')) {
            return true;
        }

        return $user->student && $feePayment->studentFee->student_id === $user->student->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin', 'finance_manager', 'accountant', 'cashier');
    }

    public function update(User $user, FeePayment $feePayment): bool
    {
        return false;
    }

    public function delete(User $user, FeePayment $feePayment): bool
    {
        return false;
    }
}
