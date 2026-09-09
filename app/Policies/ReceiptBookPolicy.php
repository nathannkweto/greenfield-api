<?php declare(strict_types=1);

namespace App\Policies;

use App\Models\ReceiptBook;
use App\Models\User;

class ReceiptBookPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin', 'finance_manager', 'accountant', 'cashier');
    }

    public function view(User $user, ReceiptBook $receiptBook): bool
    {
        if ($user->hasRole('admin', 'finance_manager', 'accountant')) {
            return true;
        }

        // Cashiers can view books assigned to them
        return $receiptBook->assigned_to_user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin', 'finance_manager');
    }

    public function update(User $user, ReceiptBook $receiptBook): bool
    {
        return $user->hasRole('admin', 'finance_manager');
    }

    public function delete(User $user, ReceiptBook $receiptBook): bool
    {
        return $user->hasRole('admin');
    }
}
