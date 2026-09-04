<?php declare(strict_types=1);

namespace App\Services\Api;

use App\Models\Course;
use App\Models\Curriculum;
use App\Models\Lecturer;
use App\Models\Program;
use App\Models\Requirement;
use App\Models\School;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use OpenAPI\Server\Api\ProgramsApiInterface;
use OpenAPI\Server\Model\CurriculumRequest;
use OpenAPI\Server\Model\NoContent200;
use OpenAPI\Server\Model\NoContent201;
use OpenAPI\Server\Model\ProgramRequest;
use OpenAPI\Server\Model\RequirementRequest;

class ProgramApiService implements ProgramsApiInterface
{
    /**
     * Create Academic Program
     */
    public function programsCreatePost(ProgramRequest $ProgramRequest): NoContent201
    {
        $school = School::query()
            ->where('public_id', $ProgramRequest->school_public_id)
            ->first();

        if (!$school) {
            throw new ModelNotFoundException('School record not found.');
        }

        Program::query()->create([
            'public_id' => Str::uuid()->toString(),
            'school_id' => $school->id,
            'code' => $ProgramRequest->code,
            'title' => $ProgramRequest->title,
            'short_description' => $ProgramRequest->short_description,
            'long_description' => $ProgramRequest->long_description,
            'level' => $ProgramRequest->level->value,
            'duration_value' => $ProgramRequest->duration_value,
            'duration_unit' => $ProgramRequest->duration_unit->value,
        ]);

        return new NoContent201();
    }

    /**
     * Edit Academic Program
     */
    public function programsPublicIdEditPost(string $public_id, ProgramRequest $ProgramRequest): NoContent200
    {
        $program = Program::query()
            ->where('public_id', $public_id)
            ->first();

        if (!$program) {
            throw new ModelNotFoundException('Program record not found.');
        }

        $school = School::query()
            ->where('public_id', $ProgramRequest->school_public_id)
            ->first();

        if (!$school) {
            throw new ModelNotFoundException('School record not found.');
        }

        $program->update([
            'school_id' => $school->id,
            'code' => $ProgramRequest->code,
            'title' => $ProgramRequest->title,
            'short_description' => $ProgramRequest->short_description,
            'long_description' => $ProgramRequest->long_description,
            'level' => $ProgramRequest->level->value,
            'duration_value' => $ProgramRequest->duration_value,
            'duration_unit' => $ProgramRequest->duration_unit->value,
        ]);

        return new NoContent200();
    }

    /**
     * Configure Program Curriculum Mapping
     */
    public function programsPublicIdCurriculumPost(string $public_id, CurriculumRequest $CurriculumRequest): NoContent200
    {
        $program = Program::query()
            ->where('public_id', $public_id)
            ->first();

        if (!$program) {
            throw new ModelNotFoundException('Program record not found.');
        }

        $course = Course::query()
            ->where('public_id', $CurriculumRequest->course_public_id)
            ->first();

        if (!$course) {
            throw new ModelNotFoundException('Course record not found.');
        }

        $lecturerId = null;
        if (!empty($CurriculumRequest->lecturer_public_id)) {
            $lecturer = Lecturer::query()
                ->where('public_id', $CurriculumRequest->lecturer_public_id)
                ->first();

            if (!$lecturer) {
                throw new ModelNotFoundException('Lecturer record not found.');
            }

            $lecturerId = $lecturer->id;
        }

        Curriculum::query()->updateOrCreate(
            [
                'program_id' => $program->id,
                'course_id'  => $course->id,
            ],
            [
                'public_id'   => Str::uuid()->toString(),
                'lecturer_id' => $lecturerId,
                'year'        => $CurriculumRequest->year,
            ]
        );

        return new NoContent200();
    }

    /**
     * Attach Admission Requirements to Program
     */
    public function programsPublicIdRequirementsPost(string $public_id, RequirementRequest $RequirementRequest): NoContent200
    {
        $program = Program::query()
            ->where('public_id', $public_id)
            ->first();

        if (!$program) {
            throw new ModelNotFoundException('Program record not found.');
        }

        $nextSortOrder = (Requirement::query()
                ->where('program_id', $program->id)
                ->max('sort_order') ?? 0) + 1;

        Requirement::query()->create([
            'public_id'   => Str::uuid()->toString(),
            'program_id'  => $program->id,
            'description' => $RequirementRequest->description,
            'sort_order'  => $nextSortOrder,
        ]);

        return new NoContent200();
    }
}
