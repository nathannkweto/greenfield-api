<?php

namespace App\Services\Delivery;

use App\Models\Assessment;
use App\Models\CourseOffering;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AssessmentService
{
    /**
     * List assessments with optional course offering and type filters.
     */
    public function listAssessments(
        ?string $offeringPublicId = null,
        ?string $type = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return Assessment::query()
            ->when($offeringPublicId, function ($query) use ($offeringPublicId) {
                $query->whereHas('course_offering', fn ($q) => $q->where('public_id', $offeringPublicId));
            })
            ->when($type, fn ($q) => $q->where('type', $type))
            ->with(['course_offering.course', 'assessment_results'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get single assessment with class submissions/results.
     */
    public function getByPublicId(string $publicId): Assessment
    {
        return Assessment::where('public_id', $publicId)
            ->with(['course_offering.course', 'assessment_results.student.user'])
            ->firstOrFail();
    }

    /**
     * Create an assessment for a course offering with syllabus weight validation.
     */
    public function createAssessment(array $data): Assessment
    {
        return DB::transaction(function () use ($data) {
            $offering = CourseOffering::where('public_id', $data['course_offering_public_id'])->firstOrFail();

            $currentTotalWeight = Assessment::where('course_offering_id', $offering->id)
                ->sum('weight_percentage');

            $newWeight = (float) $data['weight_percentage'];

            if (($currentTotalWeight + $newWeight) > 100.0) {
                throw ValidationException::withMessages([
                    'weight_percentage' => [
                        "Total assessment weight would exceed 100%. Current total: {$currentTotalWeight}%",
                    ],
                ]);
            }

            return Assessment::create([
                'public_id' => Str::uuid()->toString(),
                'course_offering_id' => $offering->id,
                'name' => $data['name'],
                'type' => $data['type'],
                'weight_percentage' => $newWeight,
                'max_score' => $data['max_score'],
                'due_date' => $data['due_date'] ?? null,
            ])->load('course_offering.course');
        });
    }

    /**
     * Update an assessment configuration and recalculate total weights.
     */
    public function updateAssessment(Assessment $assessment, array $data): Assessment
    {
        return DB::transaction(function () use ($assessment, $data) {
            if (isset($data['weight_percentage'])) {
                $newWeight = (float) $data['weight_percentage'];

                $currentTotalWeight = Assessment::where('course_offering_id', $assessment->course_offering_id)
                    ->where('id', '!=', $assessment->id)
                    ->sum('weight_percentage');

                if (($currentTotalWeight + $newWeight) > 100.0) {
                    throw ValidationException::withMessages([
                        'weight_percentage' => [
                            "Total assessment weight would exceed 100%. Remaining available: " . (100.0 - $currentTotalWeight) . "%",
                        ],
                    ]);
                }
            }

            $assessment->update(array_filter([
                'name' => $data['name'] ?? $assessment->name,
                'type' => $data['type'] ?? $assessment->type,
                'weight_percentage' => $data['weight_percentage'] ?? $assessment->weight_percentage,
                'max_score' => $data['max_score'] ?? $assessment->max_score,
                'due_date' => $data['due_date'] ?? $assessment->due_date,
            ], fn ($val) => $val !== null));

            return $assessment->fresh('course_offering.course');
        });
    }

    /**
     * Delete an assessment and clear all student scores.
     */
    public function deleteAssessment(Assessment $assessment): bool
    {
        return DB::transaction(function () use ($assessment) {
            $assessment->assessment_results()->delete();
            return $assessment->delete();
        });
    }
}
