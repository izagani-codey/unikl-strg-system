<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\RequestStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing request status values to match new enum
        \DB::table('requests')->update([
            'status_id' => \DB::raw('
                CASE status_id
                    WHEN 3 THEN 5  -- RETURNED_TO_ADMISSION: 3→5
                    WHEN 4 THEN 6  -- RETURNED_TO_STAFF_1: 4→6  
                    WHEN 5 THEN 8  -- APPROVED: 5→8
                    WHEN 6 THEN 9  -- DECLINED: 6→9
                    ELSE status_id
                END
            ')
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to old status values
        \DB::table('requests')->update([
            'status_id' => \DB::raw('
                CASE status_id
                    WHEN 5 THEN 3  -- RETURNED_TO_ADMISSION: 5→3
                    WHEN 6 THEN 4  -- RETURNED_TO_STAFF_1: 6→4
                    WHEN 8 THEN 5  -- APPROVED: 8→5
                    WHEN 9 THEN 6  -- DECLINED: 9→6
                    ELSE status_id
                END
            ')
        ]);
    }
};
