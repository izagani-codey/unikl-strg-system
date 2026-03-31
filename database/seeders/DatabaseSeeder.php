<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\RequestType;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed Request Types
        $types = [
            ['name' => 'STRG New Application',            'slug' => 'strg-new'],
            ['name' => 'Virement',                         'slug' => 'virement'],
            ['name' => 'Change Request',                   'slug' => 'change-request'],
            ['name' => 'Asset Purchase',                   'slug' => 'asset-purchase'],
            ['name' => 'Research Assistant Appointment',   'slug' => 'ra-appointment'],
            ['name' => 'Extension Request',                'slug' => 'extension'],
            ['name' => 'Project Completion / Final Report','slug' => 'final-report'],
        ];

        foreach ($types as $type) {
            RequestType::updateOrCreate(['slug' => $type['slug']], $type);
        }

        // Seed Test Users
        User::updateOrCreate(
            ['email' => 'admission@unikl.edu.my'],
            ['name' => 'Admission Student', 'password' => Hash::make('password'), 'role' => 'admission', 'email_verified_at' => now()]
        );

        User::updateOrCreate(
            ['email' => 'staff1@unikl.edu.my'],
            ['name' => 'Staff One', 'password' => Hash::make('password'), 'role' => 'staff1', 'email_verified_at' => now()]
        );

        User::updateOrCreate(
            ['email' => 'staff2@unikl.edu.my'],
            ['name' => 'Staff Two', 'password' => Hash::make('password'), 'role' => 'staff2', 'email_verified_at' => now()]
        );
    }
}