<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\RequestType;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Test Users
        User::create([
            'name'     => 'Admission Student',
            'email'    => 'admission@unikl.edu.my',
            'password' => Hash::make('password'),
            'role'     => 'admission',
        ]);

        User::create([
            'name'     => 'Staff One',
            'email'    => 'staff1@unikl.edu.my',
            'password' => Hash::make('password'),
            'role'     => 'staff1',
        ]);

        User::create([
            'name'     => 'Staff Two',
            'email'    => 'staff2@unikl.edu.my',
            'password' => Hash::make('password'),
            'role'     => 'staff2',
        ]);

        // Request Types
        $types = [
            ['name' => 'STRG New Application',           'slug' => 'strg-new'],
            ['name' => 'Virement',                        'slug' => 'virement'],
            ['name' => 'Change Request',                  'slug' => 'change-request'],
            ['name' => 'Asset Purchase',                  'slug' => 'asset-purchase'],
            ['name' => 'Research Assistant Appointment',  'slug' => 'ra-appointment'],
            ['name' => 'Extension Request',               'slug' => 'extension'],
            ['name' => 'Project Completion / Final Report','slug' => 'final-report'],
        ];

        foreach ($types as $type) {
            RequestType::create($type);
        }
    }
}