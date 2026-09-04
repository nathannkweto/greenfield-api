<?php

namespace App\Policies;

use App\Models\CourseOffering;
use App\Models\User;

class CourseOfferingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CourseOffering $offering): bool
    {
        return true;
    }
}
