<?php

namespace App\Services\Enrollment;

use App\Models\CourseOffering;
use App\Models\Enrollment;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EnrollmentService
{
    /**
     * List enrollments with filters for student, offering, or completion status.
     */
    public function listEnrollments(
        ?string $studentPublicId = null,
        ?string $offeringPublicId = null,
        ?string $status = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return Enrollment::query()
            ->when($studentPublicId, function ($query) use ($studentPublicId) {
                $query->whereHas('student', fn ($q) => $q->where('public_id', $studentPublicId));
            })
            ->when($offeringPublicId, function ($query) use ($offeringPublicId) {
                $query->whereHas('course_offering', fn ($q) => $q->where('public_id', $offeringPublicId));
            })
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with(['student.user', 'course_offering.course', 'course_offering.academic_term'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get single enrollment record by public UUID.
     */
    public function getByPublicId(string $publicId): Enrollment
    {
        return Enrollment::where('public_id', $publicId)
            ->with(['student.user', 'student.program', 'course_offering.course', 'course_offering.lecturer.user'])
            ->firstOrFail();
    }

    /**
     * Enroll a student into a course offering.
     */
    public function enrollStudent(array $data): Enrollment
    {
        return DB::transaction(function () use ($data) {
            $student = Student::where('public_id', $data['student_public_id'])->firstOrFail();
            $offering = CourseOffering::where('public_id', $data['course_offering_public_id'])->firstOrFail();

            // Prevent duplicate active enrollments for the same course offering
            $existing = Enrollment::where('student_id', $student->id)
                ->where('course_offering_id', $offering->id)
                ->whereIn('status', ['ENROLLED', 'COMPLETED'])
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'course_offering_public_id' => ['Student is already enrolled in this course offering.'],
                ]);
            }

            return Enrollment::create([
                'public_id' => Str::uuid()->toString(),
                'student_id' => $student->id,
                'course_offering_id' => $offering->id,
                'status' => $data['status'] ?? 'ENROLLED',
            ])->load(['student.user', 'course_offering.course']);
        });
    }

    /**
     * Assign grade/points and mark enrollment as COMPLETED.
     */
    public function gradeEnrollment(Enrollment $enrollment, string $grade, float $points): Enrollment
    {
        return DB::transaction(function () use ($enrollment, $grade, $points) {
            $enrollment->update([
                'status' => 'COMPLETED',
                'grade' => strtoupper($grade),
                'points' => $points,
                'completion_date' => Carbon::now(),
            ]);

            $this->recalculateStudentAcademicStats($enrollment->student);

            return $enrollment->fresh(['student', 'course_offering.course']);
        });
    }

    /**
     * Drop a course enrollment.
     */
    public function dropEnrollment(Enrollment $enrollment): Enrollment
    {
        return DB::transaction(function () use ($enrollment) {
            $enrollment->update([
                'status' => 'DROPPED',
                'drop_date' => Carbon::now(),
            ]);

            return $enrollment->fresh();
        });
    }

    /**
     * Update enrollment record details.
     */
    public function updateEnrollment(Enrollment $enrollment, array $data): Enrollment
    {
        return DB::transaction(function () use ($enrollment, $data) {
            $enrollment->update(array_filter([
                'status' => $data['status'] ?? $enrollment->status,
                'grade' => isset($data['grade']) ? strtoupper($data['grade']) : $enrollment->grade,
                'points' => $data['points'] ?? $enrollment->points,
                'completion_date' => $data['completion_date'] ?? $enrollment->completion_date,
                'drop_date' => $data['drop_date'] ?? $enrollment->drop_date,
            ], fn ($val) => $val !== null));

            if (isset($data['grade']) || isset($data['points'])) {
                $this->recalculateStudentAcademicStats($enrollment->student);
            }

            return $enrollment->fresh(['student', 'course_offering.course']);
        });
    }

    /**
     * Remove enrollment record.
     */
    public function deleteEnrollment(Enrollment $enrollment): bool
    {
        return DB::transaction(function () use ($enrollment) {
            $student = $enrollment->student;
            $deleted = $enrollment->delete();

            $this->recalculateStudentAcademicStats($student);

            return $deleted;
        });
    }

    /**
     * Calculate and update CGPA and completed unit credits on student profile.
     */
    protected function recalculateStudentAcademicStats(Student $student): void
    {
        $completedEnrollments = Enrollment::where('student_id', $student->id)
            ->where('status', 'COMPLETED')
            ->whereNotNull('points')
            ->with('course_offering.course')
            ->get();

        if ($completedEnrollments->isEmpty()) {
            $student->update([
                'cgpa' => 0.0,
                'credits_completed' => 0,
            ]);
            return;
        }

        $totalPointsEarned = 0.0;
        $totalCreditsAttempted = 0;
        $totalCreditsPassed = 0;

        foreach ($completedEnrollments as $enrollment) {
            $credits = $enrollment->course_offering->course->credits ?? 0;
            $points = $enrollment->points;

            $totalPointsEarned += ($points * $credits);
            $totalCreditsAttempted += $credits;

            if ($points > 0) {
                $totalCreditsPassed += $credits;
            }
        }

        $cgpa = $totalCreditsAttempted > 0
            ? round($totalPointsEarned / $totalCreditsAttempted, 2)
            : 0.0;

        $student->update([
            'cgpa' => $cgpa,
            'credits_completed' => $totalCreditsPassed,
        ]);
    }
}
