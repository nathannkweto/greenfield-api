<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Curriculum;
use App\Models\Lecturer;
use App\Models\Program;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CurriculumSeeder extends Seeder
{
    public function run(): void
    {
        $program = Program::where('code', 'BSCAGR')->first();
        $courses = Course::where('code', 'LIKE', 'AGR-%')->get();

        // Retrieve Lecturer 1 (John Banda) to assign to courses
        $assignedLecturer = Lecturer::where('first_name', 'John')->first();

        foreach ($courses as $index => $course) {
            $year = ($index < 2) ? 1 : 2;

            Curriculum::firstOrCreate(
                [
                    'program_id' => $program->id,
                    'course_id'  => $course->id,
                    'year'       => $year,
                ],
                [
                    'public_id'   => (string) Str::uuid(),
                    // Assigning Lecturer 1 to courses; Lecturer 2 remains unassigned
                    'lecturer_id' => $assignedLecturer->id,
                ]
            );
        }
    }
}
