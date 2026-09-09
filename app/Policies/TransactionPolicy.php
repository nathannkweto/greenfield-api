<?php declare(strict_types=1);

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin', 'finance_manager', 'accountant', 'cashier');
    }

    public function view(User $user, Transaction $transaction): bool
    {
        if ($user->hasRole('admin', 'finance_manager', 'accountant', 'cashier')) {
            return true;
        }

        // Students can view transactions associated with their accounts
        $studentAccountId = $user->student?->account?->id;
        return $studentAccountId && (
                $transaction->debit_account_id === $studentAccountId ||
                $transaction->credit_account_id === $studentAccountId
            );
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin', 'finance_manager', 'accountant', 'cashier');
    }

    /**
     * Financial immutability enforcement: Transactions must be reversed, not edited.
     */
    public function update(User $user, Transaction $transaction): bool
    {
        return false;
    }

    /**
     * Financial immutability enforcement: Transactions must never be deleted.
     */
    public function delete(User $user, Transaction $transaction): bool
    {
        return false;
    }
}
