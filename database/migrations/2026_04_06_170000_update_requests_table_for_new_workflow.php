<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            // Add new tracking fields for simplified workflow
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->timestamp('recommended_at')->nullable()->after('recommended_by');
            $table->timestamp('dean_approved_at')->nullable()->after('recommended_at');
            $table->foreignId('dean_approved_by')->nullable()->constrained('users')->after('dean_approved_at');
            
            // Add override tracking
            $table->boolean('is_override')->default(false)->after('dean_approved_by');
            
            // Add signature tracking
            $table->text('staff2_signature_data')->nullable()->after('is_override');
            $table->timestamp('staff2_signed_at')->nullable()->after('staff2_signature_data');
            $table->text('dean_signature_data')->nullable()->after('staff2_signed_at');
            $table->timestamp('dean_signed_at')->nullable()->after('dean_signature_data');
            
            // Add VOT and amount fields (move from payload)
            $table->json('vot_items')->nullable()->after('payload');
            $table->decimal('total_amount', 10, 2)->default(0)->after('vot_items');
            
            // Add deadline and priority
            $table->date('deadline')->nullable()->after('total_amount');
            $table->boolean('is_priority')->default(false)->after('deadline');
            
            // Add submitter snapshot fields
            $table->string('submitter_staff_id')->nullable()->after('is_priority');
            $table->string('submitter_designation')->nullable()->after('submitter_staff_id');
            $table->string('submitter_department')->nullable()->after('submitter_designation');
            $table->string('submitter_phone')->nullable()->after('submitter_department');
            $table->string('submitter_employee_level')->nullable()->after('submitter_phone');
            
            // Add signature and submission tracking
            $table->text('signature_data')->nullable()->after('submitter_employee_level');
            $table->timestamp('signed_at')->nullable()->after('signature_data');
            $table->timestamp('submitted_at')->nullable()->after('signed_at');
            
            // Add revision tracking
            $table->integer('revision_count')->default(0)->after('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn([
                'verified_at',
                'recommended_at', 
                'dean_approved_at',
                'dean_approved_by',
                'is_override',
                'staff2_signature_data',
                'staff2_signed_at',
                'dean_signature_data',
                'dean_signed_at',
                'vot_items',
                'total_amount',
                'deadline',
                'is_priority',
                'submitter_staff_id',
                'submitter_designation',
                'submitter_department',
                'submitter_phone',
                'submitter_employee_level',
                'signature_data',
                'signed_at',
                'submitted_at',
                'revision_count',
            ]);
        });
    }
};
