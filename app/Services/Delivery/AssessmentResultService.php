<?php

namespace App\Services\Delivery;

use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AssessmentResultService
{
    /**
     * List assessment results filtered by student or assessment.
     */
    public function listResults(
        ?string $assessmentPublicId = null,
        ?string $studentPublicId = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return AssessmentResult::query()
            ->when($assessmentPublicId, function ($query) use ($assessmentPublicId) {
                $query->whereHas('assessment', fn ($q) => $q->where('public_id', $assessmentPublicId));
            })
            ->when($studentPublicId, function ($query) use ($studentPublicId) {
                $query->whereHas('student', fn ($q) => $q->where('public_id', $studentPublicId));
            })
            ->with(['assessment.course_offering.course', 'student.user'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get a single assessment result by public UUID.
     */
    public function getByPublicId(string $publicId): AssessmentResult
    {
        return AssessmentResult::where('public_id', $publicId)
            ->with(['assessment.course_offering.course', 'student.user'])
            ->firstOrFail();
    }

    /**
     * Record or update a student's score for an assessment.
     */
    public function recordScore(array $data): AssessmentResult
    {
        return DB::transaction(function () use ($data) {
            $assessment = Assessment::where('public_id', $data['assessment_public_id'])->firstOrFail();
            $student = Student::where('public_id', $data['student_public_id'])->firstOrFail();

            $score = (float) $data['score'];

            if ($score < 0 || $score > $assessment->max_score) {
                throw ValidationException::withMessages([
                    'score' => ["Score must be between 0 and {$assessment->max_score}."],
                ]);
            }

            return AssessmentResult::updateOrCreate(
                [
                    'assessment_id' => $assessment->id,
                    'student_id' => $student->id,
                ],
                [
                    'public_id' => Str::uuid()->toString(),
                    'score' => $score,
                ]
            )->load(['assessment', 'student.user']);
        });
    }

    /**
     * Bulk upload or grade an assessment for multiple students at once.
     */
    public function bulkRecordScores(string $assessmentPublicId, array $items): Collection
    {
        return DB::transaction(function () use ($assessmentPublicId, $items) {
            $assessment = Assessment::where('public_id', $assessmentPublicId)->firstOrFail();
            $results = new Collection();

            foreach ($items as $item) {
                $student = Student::where('public_id', $item['student_public_id'])->firstOrFail();
                $score = (float) $item['score'];

                if ($score < 0 || $score > $assessment->max_score) {
                    throw ValidationException::withMessages([
                        'score' => ["Score for student {$student->student_number} must be between 0 and {$assessment->max_score}."],
                    ]);
                }

                $result = AssessmentResult::updateOrCreate(
                    [
                        'assessment_id' => $assessment->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'public_id' => Str::uuid()->toString(),
                        'score' => $score,
                    ]
                );

                $results->push($result->load('student.user'));
            }

            return $results;
        });
    }

    /**
     * Update an individual assessment result.
     */
    public function updateScore(AssessmentResult $result, float $score): AssessmentResult
    {
        return DB::transaction(function () use ($result, $score) {
            $maxScore = $result->assessment->max_score;

            if ($score < 0 || $score > $maxScore) {
                throw ValidationException::withMessages([
                    'score' => ["Score must be between 0 and {$maxScore}."],
                ]);
            }

            $result->update(['score' => $score]);

            return $result->fresh(['assessment', 'student.user']);
        });
    }

    /**
     * Remove an assessment result record.
     */
    public function deleteResult(AssessmentResult $result): bool
    {
        return $result->delete();
    }
}
