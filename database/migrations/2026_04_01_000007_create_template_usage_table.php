<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('form_templates');
            $table->foreignId('request_id')->constrained('requests');
            $table->foreignId('user_id')->constrained('users');
            $table->string('generated_file_path');
            $table->timestamps();
            
            $table->index(['template_id', 'request_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_usage');
    }
};
