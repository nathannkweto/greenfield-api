<?php

namespace App\Services\Academic;

use App\Models\Course;
use App\Models\School;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourseService
{
    /**
     * List courses with optional school filter and search.
     */
    public function listCourses(?string $schoolPublicId = null, ?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return Course::query()
            ->when($schoolPublicId, function ($query) use ($schoolPublicId) {
                $query->whereHas('school', fn ($q) => $q->where('public_id', $schoolPublicId));
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->with('school')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get a single course by public UUID.
     */
    public function getByPublicId(string $publicId): Course
    {
        return Course::where('public_id', $publicId)
            ->with(['school', 'curricula.program', 'course_offerings'])
            ->firstOrFail();
    }

    /**
     * Create a course in the catalog.
     */
    public function createCourse(array $data): Course
    {
        return DB::transaction(function () use ($data) {
            $school = School::where('public_id', $data['school_public_id'])->firstOrFail();

            return Course::create([
                'public_id' => Str::uuid()->toString(),
                'school_id' => $school->id,
                'code' => strtoupper($data['code']),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'credits' => $data['credits'],
            ]);
        });
    }

    /**
     * Update course catalog entry.
     */
    public function updateCourse(Course $course, array $data): Course
    {
        return DB::transaction(function () use ($course, $data) {
            if (isset($data['school_public_id'])) {
                $school = School::where('public_id', $data['school_public_id'])->firstOrFail();
                $data['school_id'] = $school->id;
            }

            if (isset($data['code'])) {
                $data['code'] = strtoupper($data['code']);
            }

            $course->update(array_filter($data, fn ($val) => $val !== null));

            return $course->fresh('school');
        });
    }

    /**
     * Delete a course.
     */
    public function deleteCourse(Course $course): bool
    {
        return DB::transaction(function () use ($course) {
            $course->curricula()->delete();
            return $course->delete();
        });
    }
}
