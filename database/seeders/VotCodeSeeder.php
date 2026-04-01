<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\VotCode;

class VotCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $votCodes = [
            ['code' => '10000', 'description' => 'Equipment', 'sort_order' => 1],
            ['code' => '11000', 'description' => 'Materials', 'sort_order' => 2],
            ['code' => '12000', 'description' => 'Supplies', 'sort_order' => 3],
            ['code' => '13000', 'description' => 'Travel', 'sort_order' => 4],
            ['code' => '14000', 'description' => 'Training', 'sort_order' => 5],
            ['code' => '15000', 'description' => 'Consultancy', 'sort_order' => 6],
            ['code' => '16000', 'description' => 'Maintenance', 'sort_order' => 7],
            ['code' => '17000', 'description' => 'Rental', 'sort_order' => 8],
            ['code' => '18000', 'description' => 'Utilities', 'sort_order' => 9],
            ['code' => '19000', 'description' => 'Subscriptions', 'sort_order' => 10],
            ['code' => '20000', 'description' => 'Miscellaneous', 'sort_order' => 11],
        ];

        foreach ($votCodes as $votCode) {
            VotCode::updateOrCreate(['code' => $votCode['code']], $votCode);
        }

        $this->command->info('✅ Seeded 11 VOT codes successfully');
    }
}
