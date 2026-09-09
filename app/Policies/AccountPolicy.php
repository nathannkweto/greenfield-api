<?php declare(strict_types=1);

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin', 'finance_manager', 'accountant');
    }

    public function view(User $user, Account $account): bool
    {
        if ($user->hasRole('admin', 'finance_manager', 'accountant', 'cashier')) {
            return true;
        }

        // Users or students can view their own financial ledger
        return $account->accountable_id === $user->id ||
            ($user->student && $account->accountable_id === $user->student->id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin', 'finance_manager');
    }

    public function update(User $user, Account $account): bool
    {
        return $user->hasRole('admin', 'finance_manager');
    }

    public function delete(User $user, Account $account): bool
    {
        return $user->hasRole('admin');
    }
}
