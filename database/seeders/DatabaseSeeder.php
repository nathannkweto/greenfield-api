<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            AcademicSeeder::class,
            LecturerSeeder::class,
            CurriculumSeeder::class,
            StudentSeeder::class,
        ]);
    }
}
