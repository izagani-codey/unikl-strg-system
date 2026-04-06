<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_types', function (Blueprint $table) {
            // JSON field to store dynamic form field schema
            $table->json('field_schema')->nullable()->after('description');
            // Flag for whether this type requires VOT items
            $table->boolean('requires_vot')->default(true)->after('field_schema');
            // Additional metadata for future extensibility
            $table->json('metadata')->nullable()->after('requires_vot');
        });
    }

    public function down(): void
    {
        Schema::table('request_types', function (Blueprint $table) {
            $table->dropColumn(['field_schema', 'requires_vot', 'metadata']);
        });
    }
};
