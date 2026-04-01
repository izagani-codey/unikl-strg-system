<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('override_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users'); // Staff 2 who performed override
            $table->string('action_type'); // 'approve', 'reject_reverse', 'bypass_verification'
            $table->text('reason');
            $table->json('original_data')->nullable(); // Original request state
            $table->json('new_data')->nullable(); // New request state
            $table->timestamps();
            
            $table->index(['request_id', 'user_id']);
            $table->index('action_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('override_logs');
    }
};
