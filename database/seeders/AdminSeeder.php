<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Admin 1: Super Admin
        $user1 = User::updateOrCreate(
            ['email' => 'admin@greenfield.edu'],
            [
                'public_id' => (string) Str::uuid(),
                'phone'     => '+260971234567',
                'password'  => Hash::make('password123'),
            ]
        );

        Admin::updateOrCreate(
            ['user_id' => $user1->id],
            [
                'public_id'       => (string) Str::uuid(),
                'first_name'      => 'System',
                'middle_name'     => 'Main',
                'last_name'       => 'Administrator',
                'employee_number' => 'ADM-0001',
                'department'      => 'IT & Systems Administration',
                'position'        => 'Chief Systems Administrator',
            ]
        );

        // Admin 2: Registrar
        $user2 = User::updateOrCreate(
            ['email' => 'registrar@greenfield.edu'],
            [
                'public_id' => (string) Str::uuid(),
                'phone'     => '+260971234568',
                'password'  => Hash::make('password123'),
            ]
        );

        Admin::updateOrCreate(
            ['user_id' => $user2->id],
            [
                'public_id'       => (string) Str::uuid(),
                'first_name'      => 'Jane',
                'middle_name'     => 'Mary',
                'last_name'       => 'Mwale',
                'employee_number' => 'ADM-0002',
                'department'      => 'Academic Affairs',
                'position'        => 'Academic Registrar',
            ]
        );
    }
}
