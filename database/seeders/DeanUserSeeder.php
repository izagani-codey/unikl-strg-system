<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DeanUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'dean@unikl.edu.my'],
            [
                'name' => 'Dean User',
                'password' => Hash::make('password'),
                'role' => 'dean',
                'staff_id' => 'DEAN001',
                'designation' => 'Dean',
                'department' => 'Academic Affairs',
                'phone' => '01234567890',
                'employee_level' => 'Senior Management',
                'email_verified_at' => now(),
            ]
        );
    }
}
