<?php

namespace App\Policies;

use App\Models\Lecturer;
use App\Models\User;

class LecturerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin', 'lecturer', 'student');
    }

    public function view(User $user, Lecturer $lecturer): bool
    {
        return $user->hasRole('admin', 'lecturer', 'student');
    }
}
