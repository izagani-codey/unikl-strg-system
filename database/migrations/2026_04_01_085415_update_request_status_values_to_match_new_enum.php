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
        // No changes needed - the new enum uses the same integer values
        // PENDING_VERIFICATION = 1 (was SUBMITTED)
        // PENDING_RECOMMENDATION = 2 (was STAFF1_APPROVED) 
        // PENDING_DEAN_APPROVAL = 3 (was STAFF2_APPROVED)
        // RETURNED_TO_ADMISSION = 4 (was RETURNED)
        // RETURNED_TO_STAFF_1 = 5 (new)
        // APPROVED = 6 (was DEAN_APPROVED)
        // DECLINED = 7 (was REJECTED)
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No changes needed - the new enum uses the same integer values
    }
};
