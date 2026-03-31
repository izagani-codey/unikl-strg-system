<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // system, request_returned, request_approved, etc.
            $table->string('title');
            $table->text('message');
            $table->string('url')->nullable(); // Optional link to relevant page
            $table->boolean('is_read')->default(false);
            $table->json('data')->nullable(); // Additional data like request_id, etc.
            $table->timestamps();
            
            $table->index(['user_id', 'is_read']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
