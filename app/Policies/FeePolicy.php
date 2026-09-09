<?php declare(strict_types=1);

namespace App\Policies;

use App\Models\Fee;
use App\Models\User;

class FeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin', 'finance_manager', 'accountant', 'cashier', 'student');
    }

    public function view(User $user, Fee $fee): bool
    {
        return $user->hasRole('admin', 'finance_manager', 'accountant', 'cashier', 'student');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin', 'finance_manager');
    }

    public function update(User $user, Fee $fee): bool
    {
        return $user->hasRole('admin', 'finance_manager');
    }

    public function delete(User $user, Fee $fee): bool
    {
        return $user->hasRole('admin');
    }
}
