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
        // Clear existing VOT codes to prevent duplicates
        VotCode::query()->delete();
        
        $votCodes = [
            ['code' => 'VOT11000', 'description' => 'Salary and wages', 'sort_order' => 1],
            ['code' => 'VOT14000', 'description' => 'Overtime', 'sort_order' => 2],
            ['code' => 'VOT21000', 'description' => 'Training expenses, subsistence, and local conferences', 'sort_order' => 3],
            ['code' => 'VOT22000', 'description' => 'Transportation of goods', 'sort_order' => 4],
            ['code' => 'VOT23000', 'description' => 'Communication and utilities', 'sort_order' => 5],
            ['code' => 'VOT24000', 'description' => 'Rental', 'sort_order' => 6],
            ['code' => 'VOT26000', 'description' => 'Research materials (non-consumable)', 'sort_order' => 7],
            ['code' => 'VOT27000', 'description' => 'Research materials and supplies (consumables)', 'sort_order' => 8],
            ['code' => 'VOT28000', 'description' => 'Maintenance and minor repair services', 'sort_order' => 9],
            ['code' => 'VOT29000', 'description' => 'Professional and other services (incl. printing/hospitality/honorarium)', 'sort_order' => 10],
            ['code' => 'VOT35000', 'description' => 'Assets', 'sort_order' => 11],
        ];

        foreach ($votCodes as $votCode) {
            VotCode::create($votCode);
        }

        $this->command->info('✅ Cleaned and seeded 11 VOT codes successfully');
    }
}
