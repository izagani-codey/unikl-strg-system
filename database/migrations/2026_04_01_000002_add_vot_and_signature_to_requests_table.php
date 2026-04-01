<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            // VOT line items stored as JSON array:
            // [{"vot_code": "A12345", "description": "Travel", "amount": 500.00}, ...]
            $table->json('vot_items')->nullable()->after('payload');
            $table->decimal('total_amount', 12, 2)->default(0)->after('vot_items');

            // Submitter profile snapshot at time of submission (in case profile changes later)
            $table->string('submitter_staff_id')->nullable()->after('total_amount');
            $table->string('submitter_designation')->nullable()->after('submitter_staff_id');
            $table->string('submitter_department')->nullable()->after('submitter_designation');
            $table->string('submitter_phone')->nullable()->after('submitter_department');
            $table->string('submitter_employee_level')->nullable()->after('submitter_phone');

            // Digital signature (base64 PNG from signature pad)
            $table->text('signature_data')->nullable()->after('submitter_employee_level');
            $table->timestamp('signed_at')->nullable()->after('signature_data');

            // Full datetime for submission (created_at already exists but add explicit submitted_at)
            $table->timestamp('submitted_at')->nullable()->after('signed_at');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn([
                'vot_items',
                'total_amount',
                'submitter_staff_id',
                'submitter_designation',
                'submitter_department',
                'submitter_phone',
                'submitter_employee_level',
                'signature_data',
                'signed_at',
                'submitted_at',
            ]);
        });
    }
};
