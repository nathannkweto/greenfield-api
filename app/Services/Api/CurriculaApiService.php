<?php declare(strict_types=1);

namespace App\Services\Api;

use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\Curriculum;
use App\Models\File;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenAPI\Server\Api\CurriculaApiInterface;
use OpenAPI\Server\Model\AssessmentRequest;
use OpenAPI\Server\Model\GradeRequest;
use OpenAPI\Server\Model\MaterialRequest;
use OpenAPI\Server\Model\NoContent200;
use OpenAPI\Server\Model\NoContent201;

class CurriculaApiService implements CurriculaApiInterface
{
    /**
     * Edit Assessment.
     *
     * @param string $public_id
     * @param AssessmentRequest $AssessmentRequest
     * @return NoContent200
     */
    public function curriculaAssessmentsPublicIdEditPost(
        string $public_id,
        AssessmentRequest $AssessmentRequest
    ): NoContent200 {
        $assessment = Assessment::where('public_id', $public_id)->firstOrFail();

        $assessment->update([
            'title' => $AssessmentRequest->title,
            'description' => $AssessmentRequest->description,
            'type' => $AssessmentRequest->type->value ?? $AssessmentRequest->type,
            'term' => $AssessmentRequest->term,
            'weight_percentage' => $AssessmentRequest->weight_percentage,
            'max_score' => $AssessmentRequest->max_score,
            'due_date' => $AssessmentRequest->due_date,
        ]);

        return new NoContent200();
    }

    /**
     * Record Student Grades for Assessment.
     *
     * @param string $public_id
     * @param GradeRequest $GradeRequest
     * @return NoContent200
     */
    public function curriculaAssessmentsPublicIdGradesPost(
        string $public_id,
        GradeRequest $GradeRequest
    ): NoContent200 {
        $assessment = Assessment::where('public_id', $public_id)->firstOrFail();

        DB::transaction(function () use ($assessment, $GradeRequest) {
            foreach ($GradeRequest->items as $item) {
                $student = Student::where('public_id', $item->student_public_id)->first();

                if (!$student) {
                    continue;
                }

                AssessmentResult::updateOrCreate(
                    [
                        'assessment_id' => $assessment->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'score' => $item->score,
                    ]
                );
            }
        });

        return new NoContent200();
    }

    /**
     * Edit Curriculum Material.
     *
     * @param string $public_id
     * @param MaterialRequest $MaterialRequest
     * @return NoContent200
     */
    public function curriculaMaterialsPublicIdEditPost(
        string $public_id,
        MaterialRequest $MaterialRequest
    ): NoContent200 {
        $material = File::where('public_id', $public_id)->firstOrFail();

        $material->update([
            'title' => $MaterialRequest->title ?? $material->title,
            'description' => $MaterialRequest->description ?? $material->description,
            'url' => $MaterialRequest->url ?? $material->url,
            'type' => $MaterialRequest->type ?? $material->type,
        ]);

        return new NoContent200();
    }

    /**
     * Create Curriculum Assessment.
     *
     * @param string $public_id
     * @param AssessmentRequest $AssessmentRequest
     * @return NoContent201
     */
    public function curriculaPublicIdAssessmentsCreatePost(
        string $public_id,
        AssessmentRequest $AssessmentRequest
    ): NoContent201 {
        $curriculum = Curriculum::where('public_id', $public_id)->firstOrFail();

        $curriculum->assessments()->create([
            'public_id' => (string) Str::uuid(),
            'title' => $AssessmentRequest->title,
            'description' => $AssessmentRequest->description,
            'type' => $AssessmentRequest->type->value ?? $AssessmentRequest->type,
            'term' => $AssessmentRequest->term,
            'weight_percentage' => $AssessmentRequest->weight_percentage,
            'max_score' => $AssessmentRequest->max_score,
            'due_date' => $AssessmentRequest->due_date,
        ]);

        return new NoContent201();
    }

    /**
     * Create Curriculum Material.
     *
     * @param string $public_id
     * @param MaterialRequest $MaterialRequest
     * @return NoContent201
     */
    public function curriculaPublicIdMaterialsCreatePost(
        string $public_id,
        MaterialRequest $MaterialRequest
    ): NoContent201 {
        $curriculum = Curriculum::where('public_id', $public_id)->firstOrFail();

        $curriculum->materials()->create([
            'title' => $MaterialRequest->title,
            'description' => $MaterialRequest->description ?? null,
            'url' => $MaterialRequest->url ?? null,
            'type' => $MaterialRequest->type ?? null,
        ]);

        return new NoContent201();
    }
}
