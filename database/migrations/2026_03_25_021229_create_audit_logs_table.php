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
        $table->foreignId('request_id')->constrained()->cascadeOnDelete();
        $table->foreignId('actor_id')->constrained('users');
        $table->unsignedTinyInteger('from_status');
        $table->unsignedTinyInteger('to_status');
        $table->text('note')->nullable();
        $table->timestamp('created_at');
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
