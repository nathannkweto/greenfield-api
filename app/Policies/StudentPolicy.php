<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin', 'lecturer');
    }

    public function view(User $user, ?Student $student = null): bool
    {
        // If no student instance was resolved/provided
        if (! $student) {
            return $user->hasRole('admin', 'lecturer');
        }

        // Admin and Lecturer can view anyone; students can only view their own record
        return $user->hasRole('admin', 'lecturer')
            || $user->id === $student->user_id;
    }
}
