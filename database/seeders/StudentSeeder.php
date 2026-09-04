<?php

namespace Database\Seeders;

use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $program = Program::where('code', 'BSCAGR')->first();

        // 1. Pending Student Account
        $userPending = User::updateOrCreate(
            ['email' => 'applicant.peter@gmail.com'],
            [
                'public_id' => (string) Str::uuid(),
                'phone'     => '+260978000111',
                'password'  => Hash::make('password123'),
            ]
        );

        Student::updateOrCreate(
            ['user_id' => $userPending->id],
            [
                'public_id'          => (string) Str::uuid(),
                'program_id'         => $program->id,
                'application_number' => 'APP-2026-0001',
                'admission_number'   => null,
                'student_number'     => null,
                'first_name'         => 'Peter',
                'middle_names'       => 'Chileshe',
                'last_name'          => 'Mulenga',
                'email'              => 'applicant.peter@gmail.com',
                'phone'              => '+260978000111',
                'dob'                => '2003-04-12',
                'address'            => '123 Parklands, Kitwe',
                'emergency_contact'  => '+260978000000',
                'sex'                => 'male',
                'marital_status'     => 'single',
                'nationality'        => 'Zambian',
                'nrc_number'         => '111111/11/1',
                'intake'             => 'January',
                'study_mode'         => 'full_time',
                'status'             => 'Pending',
                'application_date'   => '2026-01-10',
            ]
        );

        // 2. Registered Student Account
        $userRegistered = User::updateOrCreate(
            ['email' => 'student.mwamba@gmail.com'],
            [
                'public_id' => (string) Str::uuid(),
                'phone'     => '+260978000222',
                'password'  => Hash::make('password123'),
            ]
        );

        $registeredStudent = Student::updateOrCreate(
            ['user_id' => $userRegistered->id],
            [
                'public_id'          => (string) Str::uuid(),
                'program_id'         => $program->id,
                'application_number' => 'APP-2026-0002',
                'admission_number'   => 'ADM-2026-0002',
                'student_number'     => '20260002',
                'first_name'         => 'Mwamba',
                'middle_names'       => 'Grace',
                'last_name'          => 'Tembo',
                'email'              => 'student.mwamba@gmail.com',
                'phone'              => '+260978000222',
                'dob'                => '2002-08-25',
                'address'            => '45 Sunningdale, Lusaka',
                'emergency_contact'  => '+260978999999',
                'sex'                => 'female',
                'marital_status'     => 'single',
                'nationality'        => 'Zambian',
                'nrc_number'         => '222222/22/1',
                'intake'             => 'January',
                'study_mode'         => 'full_time',
                'status'             => 'Registered',
                'application_date'   => '2026-01-05',
                'admission_date'     => '2026-01-15',
                'cgpa'               => 3.50,
                'credits_completed'  => 12,
            ]
        );

        // Enroll Registered Student in Curriculum
        $curriculumEntries = Curriculum::where('program_id', $program->id)->get();
        foreach ($curriculumEntries as $curriculum) {
            Enrollment::firstOrCreate(
                [
                    'student_id'    => $registeredStudent->id,
                    'curriculum_id' => $curriculum->id,
                ],
                [
                    'public_id' => (string) Str::uuid(),
                    'status'    => 'In Progress',
                ]
            );
        }
    }
}
