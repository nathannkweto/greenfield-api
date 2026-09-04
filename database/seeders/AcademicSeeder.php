<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Program;
use App\Models\Requirement;
use App\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AcademicSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Schools
        $schoolsData = [
            'agric' => [
                'name'        => 'School of Agriculture',
                'description' => 'Explore sustainable farming techniques, soil science, and agricultural business.',
            ],
            'bus' => [
                'name'        => 'School of Business',
                'description' => 'Develop strategic leadership, marketing, accounting, and organizational management skills.',
            ],
            'edu' => [
                'name'        => 'School of Education',
                'description' => 'Shaping future educators with advanced instructional techniques and educational theory.',
            ],
        ];

        $schools = [];
        foreach ($schoolsData as $key => $data) {
            $schools[$key] = School::firstOrCreate(
                ['name' => $data['name']],
                [
                    'public_id'   => (string) Str::uuid(),
                    'description' => $data['description'],
                ]
            );
        }

        // 2. Define Programs & Courses Data
        $programs = [
            [
                'school_key'        => 'agric',
                'code'              => 'BSCAGR',
                'title'             => 'B.Sc. in Agricultural Science',
                'level'             => 'Degree',
                'duration_value'    => 4,
                'duration_unit'     => 'years',
                'short_description' => 'Learn sustainable farming techniques, soil science, and modern crop production.',
                'long_description'  => 'The B.Sc. in Agricultural Science equips students with scientific knowledge and practical skills.',
                'requirements'      => [
                    'Grade 12 Certificate with credit in Biology, Chemistry, and Math.',
                    'Pass in university entrance exam.',
                ],
                'curriculum'        => [
                    1 => [
                        ['code' => 'AGR-101', 'title' => 'Intro to Agriculture'],
                        ['code' => 'AGR-102', 'title' => 'General Biology'],
                    ],
                    2 => [
                        ['code' => 'AGR-201', 'title' => 'Crop Production'],
                        ['code' => 'AGR-202', 'title' => 'Genetics & Breeding'],
                    ],
                ],
            ],
            [
                'school_key'        => 'bus',
                'code'              => 'BBA',
                'title'             => 'Bachelor of Business Administration',
                'level'             => 'Degree',
                'duration_value'    => 4,
                'duration_unit'     => 'years',
                'short_description' => 'Develop strategic leadership, marketing, and organizational management skills.',
                'long_description'  => 'The BBA program is designed to develop visionary business leaders for a global economy.',
                'requirements'      => [
                    'Grade 12 Certificate with credit in Math and English.',
                ],
                'curriculum'        => [
                    1 => [
                        ['code' => 'BUS-101', 'title' => 'Principles of Management'],
                        ['code' => 'BUS-102', 'title' => 'Microeconomics'],
                    ],
                    2 => [
                        ['code' => 'BUS-201', 'title' => 'Marketing Management'],
                        ['code' => 'BUS-202', 'title' => 'Organizational Behavior'],
                    ],
                ],
            ],
        ];

        // 3. Execute Seeding for Programs, Requirements & Courses
        foreach ($programs as $progData) {
            $school = $schools[$progData['school_key']];

            $program = Program::firstOrCreate(
                ['code' => $progData['code']],
                [
                    'public_id'         => (string) Str::uuid(),
                    'school_id'         => $school->id,
                    'title'             => $progData['title'],
                    'level'             => $progData['level'],
                    'duration_value'    => $progData['duration_value'],
                    'duration_unit'     => $progData['duration_unit'],
                    'short_description' => $progData['short_description'],
                    'long_description'  => $progData['long_description'],
                ]
            );

            Requirement::where('program_id', $program->id)->delete();
            foreach ($progData['requirements'] as $index => $reqText) {
                Requirement::create([
                    'public_id'   => (string) Str::uuid(),
                    'program_id'  => $program->id,
                    'description' => $reqText,
                    'sort_order'  => $index + 1,
                ]);
            }

            foreach ($progData['curriculum'] as $year => $coursesList) {
                foreach ($coursesList as $cData) {
                    Course::firstOrCreate(
                        ['code' => $cData['code']],
                        [
                            'public_id'   => (string) Str::uuid(),
                            'school_id'   => $school->id,
                            'title'       => $cData['title'],
                            'description' => "Course module for {$cData['title']}",
                            'credits'     => 3,
                        ]
                    );
                }
            }
        }
    }
}
