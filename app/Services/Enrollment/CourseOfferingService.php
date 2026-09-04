<?php

namespace App\Services\Enrollment;

use App\Models\AcademicTerm;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Lecturer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourseOfferingService
{
    /**
     * List course offerings with optional filters for term, lecturer, or course.
     */
    public function listOfferings(
        ?string $termPublicId = null,
        ?string $lecturerPublicId = null,
        ?string $coursePublicId = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return CourseOffering::query()
            ->when($termPublicId, function ($query) use ($termPublicId) {
                $query->whereHas('academic_term', fn ($q) => $q->where('public_id', $termPublicId));
            })
            ->when($lecturerPublicId, function ($query) use ($lecturerPublicId) {
                $query->whereHas('lecturer', fn ($q) => $q->where('public_id', $lecturerPublicId));
            })
            ->when($coursePublicId, function ($query) use ($coursePublicId) {
                $query->whereHas('course', fn ($q) => $q->where('public_id', $coursePublicId));
            })
            ->with(['course', 'academic_term', 'lecturer.user'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get single course offering with full roster and material details.
     */
    public function getByPublicId(string $publicId): CourseOffering
    {
        return CourseOffering::where('public_id', $publicId)
            ->with(['course', 'academic_term', 'lecturer.user', 'enrollments.student.user', 'assessments', 'course_materials'])
            ->firstOrFail();
    }

    /**
     * Schedule a new course offering for an academic term.
     */
    public function createOffering(array $data): CourseOffering
    {
        return DB::transaction(function () use ($data) {
            $course = Course::where('public_id', $data['course_public_id'])->firstOrFail();
            $term = AcademicTerm::where('public_id', $data['term_public_id'])->firstOrFail();

            $lecturerId = null;
            if (!empty($data['lecturer_public_id'])) {
                $lecturer = Lecturer::where('public_id', $data['lecturer_public_id'])->firstOrFail();
                $lecturerId = $lecturer->id;
            }

            return CourseOffering::create([
                'public_id' => Str::uuid()->toString(),
                'course_id' => $course->id,
                'term_id' => $term->id,
                'lecturer_id' => $lecturerId,
            ])->load(['course', 'academic_term', 'lecturer.user']);
        });
    }

    /**
     * Assign or replace instructor for a course offering.
     */
    public function assignLecturer(CourseOffering $offering, ?string $lecturerPublicId): CourseOffering
    {
        return DB::transaction(function () use ($offering, $lecturerPublicId) {
            $lecturerId = null;

            if ($lecturerPublicId) {
                $lecturer = Lecturer::where('public_id', $lecturerPublicId)->firstOrFail();
                $lecturerId = $lecturer->id;
            }

            $offering->update(['lecturer_id' => $lecturerId]);

            return $offering->fresh(['course', 'academic_term', 'lecturer.user']);
        });
    }

    /**
     * Update offering attributes.
     */
    public function updateOffering(CourseOffering $offering, array $data): CourseOffering
    {
        return DB::transaction(function () use ($offering, $data) {
            if (array_key_exists('lecturer_public_id', $data)) {
                if ($data['lecturer_public_id'] !== null) {
                    $lecturer = Lecturer::where('public_id', $data['lecturer_public_id'])->firstOrFail();
                    $data['lecturer_id'] = $lecturer->id;
                } else {
                    $data['lecturer_id'] = null;
                }
            }

            $offering->update(array_filter([
                'lecturer_id' => array_key_exists('lecturer_id', $data) ? $data['lecturer_id'] : $offering->lecturer_id,
            ], fn ($val) => $val !== null || array_key_exists('lecturer_id', $data)));

            return $offering->fresh(['course', 'academic_term', 'lecturer.user']);
        });
    }

    /**
     * Delete course offering and dependent course records.
     */
    public function deleteOffering(CourseOffering $offering): bool
    {
        return DB::transaction(function () use ($offering) {
            $offering->enrollments()->delete();
            $offering->assessments()->delete();
            $offering->course_materials()->delete();

            return $offering->delete();
        });
    }
}
