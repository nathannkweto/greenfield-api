<?php

namespace Database\Seeders;

use App\Models\Lecturer;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LecturerSeeder extends Seeder
{
    public function run(): void
    {
        $agricSchool = School::where('name', 'School of Agriculture')->first();
        $businessSchool = School::where('name', 'School of Business')->first();

        // Lecturer 1: Assigned to curriculum courses
        $user1 = User::updateOrCreate(
            ['email' => 'john.banda@greenfield.edu'],
            [
                'public_id' => (string) Str::uuid(),
                'phone'     => '+260977100200',
                'password'  => Hash::make('password123'),
            ]
        );

        Lecturer::updateOrCreate(
            ['user_id' => $user1->id],
            [
                'public_id'         => (string) Str::uuid(),
                'school_id'         => $agricSchool->id,
                'first_name'        => 'John',
                'middle_name'       => 'Kaluba',
                'last_name'         => 'Banda',
                'dob'               => '1982-05-14',
                'address'           => 'Plot 45, Great East Road, Lusaka',
                'emergency_contact' => '+260977111222',
            ]
        );

        // Lecturer 2: Unassigned (No courses in curriculum)
        $user2 = User::updateOrCreate(
            ['email' => 'mary.phiri@greenfield.edu'],
            [
                'public_id' => (string) Str::uuid(),
                'phone'     => '+260977300400',
                'password'  => Hash::make('password123'),
            ]
        );

        Lecturer::updateOrCreate(
            ['user_id' => $user2->id],
            [
                'public_id'         => (string) Str::uuid(),
                'school_id'         => $businessSchool->id,
                'first_name'        => 'Mary',
                'middle_name'       => 'Chileshe',
                'last_name'         => 'Phiri',
                'dob'               => '1988-11-20',
                'address'           => 'Plot 12, Woodlands, Lusaka',
                'emergency_contact' => '+260977333444',
            ]
        );
    }
}
