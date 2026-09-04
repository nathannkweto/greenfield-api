<?php

namespace App\Models\Traits;

trait HasPortalRoles
{
    public function isAdmin(): bool
    {
        return $this->admins()->exists();
    }

    public function isLecturer(): bool
    {
        return $this->lecturers()->exists();
    }

    public function isStudent(): bool
    {
        return $this->students()->exists();
    }

    public function isApplicant(): bool
    {
        return $this->applicants()->exists();
    }

    public function hasRole(string ...$roles): bool
    {
        foreach ($roles as $role) {
            if ($role === 'admin' && $this->isAdmin()) return true;
            if ($role === 'lecturer' && $this->isLecturer()) return true;
            if ($role === 'student' && $this->isStudent()) return true;
            if ($role === 'applicant' && $this->isApplicant()) return true;
        }
        return false;
    }
}
