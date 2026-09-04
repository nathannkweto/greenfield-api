<?php

namespace App\Services\Academic;

use App\Models\School;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SchoolService
{
    /**
     * List paginated schools with search filter.
     */
    public function listSchools(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return School::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->with(['user', 'programs', 'courses'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get single school by public UUID.
     */
    public function getByPublicId(string $publicId): School
    {
        return School::where('public_id', $publicId)
            ->with(['user', 'programs', 'courses', 'lecturers.user'])
            ->firstOrFail();
    }

    /**
     * Create a new school record.
     */
    public function createSchool(array $data): School
    {
        return DB::transaction(function () use ($data) {
            $deanId = null;
            if (!empty($data['dean_user_public_id'])) {
                $user = User::where('public_id', $data['dean_user_public_id'])->firstOrFail();
                $deanId = $user->id;
            }

            return School::create([
                'public_id' => Str::uuid()->toString(),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'dean_id' => $deanId,
            ]);
        });
    }

    /**
     * Update an existing school.
     */
    public function updateSchool(School $school, array $data): School
    {
        return DB::transaction(function () use ($school, $data) {
            if (array_key_exists('dean_user_public_id', $data)) {
                if ($data['dean_user_public_id'] !== null) {
                    $user = User::where('public_id', $data['dean_user_public_id'])->firstOrFail();
                    $data['dean_id'] = $user->id;
                } else {
                    $data['dean_id'] = null;
                }
            }

            $school->update(array_filter([
                'name' => $data['name'] ?? $school->name,
                'description' => $data['description'] ?? $school->description,
                'dean_id' => array_key_exists('dean_id', $data) ? $data['dean_id'] : $school->dean_id,
            ], fn ($val) => $val !== null || array_key_exists('dean_id', $data)));

            return $school->fresh('user');
        });
    }

    /**
     * Assign or replace the Dean of a school.
     */
    public function assignDean(School $school, string $userPublicId): School
    {
        $user = User::where('public_id', $userPublicId)->firstOrFail();

        $school->update(['dean_id' => $user->id]);

        return $school->fresh('user');
    }

    /**
     * Delete a school.
     */
    public function deleteSchool(School $school): bool
    {
        return DB::transaction(function () use ($school) {
            return $school->delete();
        });
    }
}
