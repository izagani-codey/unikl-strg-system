<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            VotCodeSeeder::class,
            RequestTypeSeeder::class,
        ]);

        // Remove old admission user if exists
        User::where('email', 'admissions@unikl.edu.my')->delete();

        // Seed Updated Admissions Dev Account with complete profile
        User::updateOrCreate(
            ['email' => 'admissions@unikl.edu.my'],
            [
                'name' => 'Admissions Dev User',
                'password' => Hash::make('password'),
                'role' => 'admission',
                'email_verified_at' => now(),
                // Complete staff profile
                'staff_id' => 'DEV001',
                'designation' => 'Admissions Officer',
                'department' => 'Student Affairs',
                'phone' => '+60123456789',
                'employee_level' => 'Executive',
                // Enable dev features
                'override_enabled' => false,
                'override_enabled_at' => null,
            ]
        );

        // Staff 1 User
        User::updateOrCreate(
            ['email' => 'staff1@unikl.edu.my'],
            [
                'name' => 'Staff One', 
                'password' => Hash::make('password'), 
                'role' => 'staff1', 
                'email_verified_at' => now(),
                'staff_id' => 'STF001',
                'designation' => 'Senior Lecturer',
                'department' => 'Academic Affairs',
                'phone' => '+60123456780',
                'employee_level' => 'Senior Executive',
            ]
        );

        // Staff 2 User (with override enabled)
        User::updateOrCreate(
            ['email' => 'staff2@unikl.edu.my'],
            [
                'name' => 'Staff Two', 
                'password' => Hash::make('password'), 
                'role' => 'staff2', 
                'email_verified_at' => now(),
                'staff_id' => 'STF002',
                'designation' => 'Director',
                'department' => 'Academic Affairs',
                'phone' => '+60123456781',
                'employee_level' => 'Management',
                'override_enabled' => true,
                'override_enabled_at' => now(),
            ]
        );

        // Dean User
        User::updateOrCreate(
            ['email' => 'dean@unikl.edu.my'],
            [
                'name' => 'Department Dean', 
                'password' => Hash::make('password'), 
                'role' => 'dean', 
                'email_verified_at' => now(),
                'staff_id' => 'DEAN001',
                'designation' => 'Dean',
                'department' => 'Academic Affairs',
                'phone' => '+60123456782',
                'employee_level' => 'Management',
                'override_enabled' => false,
                'override_enabled_at' => null,
            ]
        );

        // Admin User
        User::updateOrCreate(
            ['email' => 'admin@unikl.edu.my'],
            [
                'name' => 'System Administrator', 
                'password' => Hash::make('password'), 
                'role' => 'admin', 
                'email_verified_at' => now(),
                'staff_id' => 'ADM001',
                'designation' => 'System Administrator',
                'department' => 'IT Department',
                'phone' => '+60123456783',
                'employee_level' => 'Management',
                'override_enabled' => false,
                'override_enabled_at' => null,
            ]
        );

        // Create templates after users and request types are created
        $this->call([
            TemplateSeeder::class,
        ]);

        $this->command->info('Seeded updated development accounts:');
        $this->command->info('   admissions@unikl.edu.my (password) - Admissions Dev User');
        $this->command->info('   staff1@unikl.edu.my (password) - Staff One');
        $this->command->info('   staff2@unikl.edu.my (password) - Staff Two (Override Enabled)');
        $this->command->info('   dean@unikl.edu.my (password) - Department Dean');
        $this->command->info('   admin@unikl.edu.my (password) - System Administrator');
    }
}