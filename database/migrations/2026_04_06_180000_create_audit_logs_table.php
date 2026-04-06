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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained()->onDelete('cascade');
            $table->foreignId('actor_id')->constrained('users')->onDelete('cascade');
            $table->string('actor_role'); // admission, staff1, staff2, dean
            $table->string('action'); // staff1_approved, staff2_approved, dean_approved, returned, rejected, override_staff1
            $table->integer('from_status');
            $table->integer('to_status');
            $table->text('note')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_override')->default(false);
            $table->string('signature_data')->nullable(); // 'signature_provided' or null
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['request_id', 'created_at']);
            $table->index(['actor_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['is_override']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
