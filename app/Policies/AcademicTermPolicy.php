<?php

namespace App\Policies;

use App\Models\AcademicTerm;
use App\Models\User;

class AcademicTermPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AcademicTerm $term): bool
    {
        return true;
    }
}
