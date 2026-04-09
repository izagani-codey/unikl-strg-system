<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class LoadTestSeeder extends Seeder  // ← THIS WAS MISSING
{                                    // ← AND THIS
    public function run()
    {
        for ($i = 0; $i < 500; $i++) {
            \App\Models\Request::create([
                'user_id' => User::inRandomOrder()->first()->id,
                'request_type_id' => 1,
                'ref_number' => 'TEST-' . $i,
                'status_id' => rand(1, 6),
                'payload' => ['description' => 'test'],
                'total_amount' => rand(100, 5000),
                'submitted_at' => now()->subDays(rand(1, 365)),
                'created_at' => now()->subDays(rand(1, 365)),
            ]);
        }
    }
}               // ← AND THIS