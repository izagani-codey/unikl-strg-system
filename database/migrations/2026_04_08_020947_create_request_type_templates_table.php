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
        Schema::create('request_type_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('form_template_id')->constrained()->onDelete('cascade');
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['request_type_id', 'form_template_id'], 'unique_request_type_template');
            $table->index(['request_type_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_type_templates');
    }
};
