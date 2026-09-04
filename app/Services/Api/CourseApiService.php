<?php declare(strict_types=1);

namespace App\Services\Api;

use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\CourseOffering;
use App\Models\Enrollment;
use App\Models\School;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenAPI\Server\Api\CoursesApiInterface;
use OpenAPI\Server\Model\AssessmentRequest;
use OpenAPI\Server\Model\CourseOfferingRequest;
use OpenAPI\Server\Model\CourseRequest;
use OpenAPI\Server\Model\FinalizeGradeRequest;
use OpenAPI\Server\Model\GradeRequest;
use OpenAPI\Server\Model\MaterialRequest;
use OpenAPI\Server\Model\NoContent200;
use OpenAPI\Server\Model\NoContent201;

class CourseApiService implements CoursesApiInterface
{
    /**
     * Create Term Course Offering
     */
    public function courseOfferingsCreatePost(CourseOfferingRequest $CourseOfferingRequest): NoContent201
    {
        $course = Course::query()
            ->where('public_id', $CourseOfferingRequest->course_public_id ?? null)
            ->first();

        if (!$course) {
            throw new ModelNotFoundException('Course record not found.');
        }

        $termId = null;
        if (!empty($CourseOfferingRequest->term_public_id ?? null)) {
            $term = Term::query()->where('public_id', $CourseOfferingRequest->term_public_id)->first();
            $termId = $term?->id;
        }

        CourseOffering::query()->create([
            'public_id' => Str::uuid()->toString(),
            'course_id' => $course->id,
            'term_id' => $termId,
            'section' => $CourseOfferingRequest->section ?? null,
            'capacity' => $CourseOfferingRequest->capacity ?? null,
        ]);

        return new NoContent201();
    }

    /**
     * Edit Term Course Offering
     */
    public function courseOfferingsPublicIdEditPost(string $public_id, CourseOfferingRequest $CourseOfferingRequest): NoContent200
    {
        $offering = CourseOffering::query()
            ->where('public_id', $public_id)
            ->first();

        if (!$offering) {
            throw new ModelNotFoundException('Course offering record not found.');
        }

        $course = Course::query()
            ->where('public_id', $CourseOfferingRequest->course_public_id ?? null)
            ->first();

        if (!$course) {
            throw new ModelNotFoundException('Course record not found.');
        }

        $termId = $offering->term_id;
        if (!empty($CourseOfferingRequest->term_public_id ?? null)) {
            $term = Term::query()->where('public_id', $CourseOfferingRequest->term_public_id)->first();
            $termId = $term?->id;
        }

        $offering->update([
            'course_id' => $course->id,
            'term_id' => $termId,
            'section' => $CourseOfferingRequest->section ?? null,
            'capacity' => $CourseOfferingRequest->capacity ?? null,
        ]);

        return new NoContent200();
    }

    /**
     * Create Course Catalog Item
     */
    public function coursesCreatePost(CourseRequest $CourseRequest): NoContent201
    {
        $school = School::query()
            ->where('public_id', $CourseRequest->school_public_id)
            ->first();

        if (!$school) {
            throw new ModelNotFoundException('School record not found.');
        }

        Course::query()->create([
            'public_id' => Str::uuid()->toString(),
            'school_id' => $school->id,
            'code' => $CourseRequest->code,
            'title' => $CourseRequest->title,
            'description' => $CourseRequest->description,
            'credits' => $CourseRequest->credits,
        ]);

        return new NoContent201();
    }

    /**
     * Edit Course Information
     */
    public function coursesPublicIdEditPost(string $public_id, CourseRequest $CourseRequest): NoContent200
    {
        $course = Course::query()
            ->where('public_id', $public_id)
            ->first();

        if (!$course) {
            throw new ModelNotFoundException('Course record not found.');
        }

        $school = School::query()
            ->where('public_id', $CourseRequest->school_public_id)
            ->first();

        if (!$school) {
            throw new ModelNotFoundException('School record not found.');
        }

        $course->update([
            'school_id' => $school->id,
            'code' => $CourseRequest->code,
            'title' => $CourseRequest->title,
            'description' => $CourseRequest->description,
            'credits' => $CourseRequest->credits,
        ]);

        return new NoContent200();
    }

    /**
     * Create Course Material
     */
    public function coursesMaterialCreatePost(MaterialRequest $MaterialRequest): NoContent201
    {
        $offering = CourseOffering::query()
            ->where('public_id', $MaterialRequest->course_offering_public_id)
            ->first();

        if (!$offering) {
            throw new ModelNotFoundException('Course offering record not found.');
        }

        CourseMaterial::query()->create([
            'public_id' => Str::uuid()->toString(),
            'course_offering_id' => $offering->id,
            'title' => $MaterialRequest->title,
            'type' => $MaterialRequest->type,
            'url' => $MaterialRequest->url,
        ]);

        return new NoContent201();
    }

    /**
     * Edit Course Material
     */
    public function coursesMaterialPublicIdEditPost(string $public_id, MaterialRequest $MaterialRequest): NoContent200
    {
        $material = CourseMaterial::query()
            ->where('public_id', $public_id)
            ->first();

        if (!$material) {
            throw new ModelNotFoundException('Course material record not found.');
        }

        $offering = CourseOffering::query()
            ->where('public_id', $MaterialRequest->course_offering_public_id)
            ->first();

        if (!$offering) {
            throw new ModelNotFoundException('Course offering record not found.');
        }

        $material->update([
            'course_offering_id' => $offering->id,
            'title' => $MaterialRequest->title,
            'type' => $MaterialRequest->type,
            'url' => $MaterialRequest->url,
        ]);

        return new NoContent200();
    }

    /**
     * Create Course Assessment
     */
    public function coursesAssessmentsCreatePost(AssessmentRequest $AssessmentRequest): NoContent201
    {
        $offering = CourseOffering::query()
            ->where('public_id', $AssessmentRequest->course_offering_public_id)
            ->first();

        if (!$offering) {
            throw new ModelNotFoundException('Course offering record not found.');
        }

        Assessment::query()->create([
            'public_id' => Str::uuid()->toString(),
            'course_offering_id' => $offering->id,
            'title' => $AssessmentRequest->name,
            'type' => $AssessmentRequest->type,
            'weight_percentage' => $AssessmentRequest->weight_percentage,
            'max_score' => $AssessmentRequest->max_score,
        ]);

        return new NoContent201();
    }

    /**
     * Edit Assessment
     */
    public function coursesAssessmentsPublicIdEditPost(string $public_id, AssessmentRequest $AssessmentRequest): NoContent200
    {
        $assessment = Assessment::query()
            ->where('public_id', $public_id)
            ->first();

        if (!$assessment) {
            throw new ModelNotFoundException('Assessment record not found.');
        }

        $offering = CourseOffering::query()
            ->where('public_id', $AssessmentRequest->course_offering_public_id)
            ->first();

        if (!$offering) {
            throw new ModelNotFoundException('Course offering record not found.');
        }

        $assessment->update([
            'course_offering_id' => $offering->id,
            'title' => $AssessmentRequest->name,
            'type' => $AssessmentRequest->type,
            'weight_percentage' => $AssessmentRequest->weight_percentage,
            'max_score' => $AssessmentRequest->max_score,
        ]);

        return new NoContent200();
    }

    /**
     * Record Student Grades for Assessment
     */
    public function coursesAssessmentsPublicIdGradesPost(string $public_id, GradeRequest $GradeRequest): NoContent200
    {
        $assessment = Assessment::query()
            ->where('public_id', $public_id)
            ->first();

        if (!$assessment) {
            throw new ModelNotFoundException('Assessment record not found.');
        }

        DB::transaction(function () use ($assessment, $GradeRequest) {
            $items = $GradeRequest->items ?? [$GradeRequest];

            foreach ($items as $item) {
                $studentPublicId = $item->student_public_id ?? null;
                if (!$studentPublicId) {
                    continue;
                }

                $student = Student::query()
                    ->where('public_id', $studentPublicId)
                    ->first();

                if (!$student) {
                    continue;
                }

                AssessmentResult::query()->updateOrCreate(
                    [
                        'assessment_id' => $assessment->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'score' => $item->score ?? 0,
                    ]
                );
            }
        });

        return new NoContent200();
    }

    /**
     * Finalize Course Grade & Compute GPA
     */
    public function enrollmentsPublicIdFinalizeGradePost(string $public_id, FinalizeGradeRequest $FinalizeGradeRequest): NoContent200
    {
        $enrollment = Enrollment::query()
            ->where('public_id', $public_id)
            ->first();

        if (!$enrollment) {
            throw new ModelNotFoundException('Enrollment record not found.');
        }

        $enrollment->update([
            'final_grade' => $FinalizeGradeRequest->final_grade ?? $FinalizeGradeRequest->grade ?? null,
            'status' => 'finalized',
        ]);

        return new NoContent200();
    }
}
