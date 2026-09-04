<?php

namespace App\Services\Academic;

use App\Models\Course;
use App\Models\Curriculum;
use App\Models\Program;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CurriculumService
{
    /**
     * Get the complete curriculum for a specific program.
     */
    public function getProgramCurriculum(string $programPublicId): Collection
    {
        $program = Program::where('public_id', $programPublicId)->firstOrFail();

        return Curriculum::where('program_id', $program->id)
            ->with('course')
            ->orderBy('year_level')
            ->orderBy('term_number')
            ->get();
    }

    /**
     * Map a course to a program's curriculum.
     */
    public function addCourseToCurriculum(array $data): Curriculum
    {
        return DB::transaction(function () use ($data) {
            $program = Program::where('public_id', $data['program_public_id'])->firstOrFail();
            $course = Course::where('public_id', $data['course_public_id'])->firstOrFail();

            return Curriculum::updateOrCreate(
                [
                    'program_id' => $program->id,
                    'course_id' => $course->id,
                ],
                [
                    'year_level' => $data['year_level'],
                    'term_number' => $data['term_number'],
                ]
            )->load(['program', 'course']);
        });
    }

    /**
     * Update an existing curriculum entry.
     */
    public function updateCurriculum(int $id, array $data): Curriculum
    {
        $curriculum = Curriculum::findOrFail($id);

        $curriculum->update(array_filter([
            'year_level' => $data['year_level'] ?? $curriculum->year_level,
            'term_number' => $data['term_number'] ?? $curriculum->term_number,
        ]));

        return $curriculum->fresh(['program', 'course']);
    }

    /**
     * Bulk sync courses for a program curriculum.
     */
    public function syncProgramCurriculum(string $programPublicId, array $items): Collection
    {
        return DB::transaction(function () use ($programPublicId, $items) {
            $program = Program::where('public_id', $programPublicId)->firstOrFail();

            Curriculum::where('program_id', $program->id)->delete();

            $created = new Collection();
            foreach ($items as $item) {
                $course = Course::where('public_id', $item['course_public_id'])->firstOrFail();

                $entry = Curriculum::create([
                    'program_id' => $program->id,
                    'course_id' => $course->id,
                    'year_level' => $item['year_level'],
                    'term_number' => $item['term_number'],
                ]);

                $created->push($entry->load('course'));
            }

            return $created;
        });
    }

    /**
     * Remove a course from a curriculum by entry ID.
     */
    public function removeCourseFromCurriculum(int $id): bool
    {
        $curriculum = Curriculum::findOrFail($id);
        return $curriculum->delete();
    }
}
