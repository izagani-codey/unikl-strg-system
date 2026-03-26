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
    Schema::create('requests', function (Blueprint $table) {
        $table->id();
        $table->string('ref_number')->unique();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('request_type_id')->constrained();
        
        // Status & Workflow (1: Pending, 2: Verified, 3: Needs Revision, 4: Approved, 5: Declined)
        $table->integer('status_id')->default(1);
        
        // Data & Files
        $table->json('payload'); // Stores amount and description
        $table->string('file_path')->nullable(); 
        
        // Audit Trail (The "Verified By" details)
        $table->foreignId('verified_by')->nullable()->constrained('users');
        $table->foreignId('recommended_by')->nullable()->constrained('users');
        
        // Comments/Reasons
        $table->text('staff_notes')->nullable(); // Internal
        $table->text('rejection_reason')->nullable(); // Visible to Admissions
        
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
