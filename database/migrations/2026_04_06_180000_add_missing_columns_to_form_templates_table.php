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
        if (!Schema::hasTable('form_templates')) {
            return;
        }

        Schema::table('form_templates', function (Blueprint $table) {
            // Only add columns that don't exist
            $columnsToAdd = [];
            
            if (!Schema::hasColumn('form_templates', 'template_type')) {
                $table->string('template_type')->default('general_form')->after('title');
                $columnsToAdd[] = 'template_type';
            }
            
            if (!Schema::hasColumn('form_templates', 'field_mappings')) {
                $table->json('field_mappings')->nullable()->after('file_path');
                $columnsToAdd[] = 'field_mappings';
            }
            
            if (!Schema::hasColumn('form_templates', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('field_mappings');
                $columnsToAdd[] = 'is_active';
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('form_templates')) {
            return;
        }

        Schema::table('form_templates', function (Blueprint $table) {
            $columnsToDrop = [];
            
            if (Schema::hasColumn('form_templates', 'template_type')) {
                $columnsToDrop[] = 'template_type';
            }
            
            if (Schema::hasColumn('form_templates', 'field_mappings')) {
                $columnsToDrop[] = 'field_mappings';
            }
            
            if (Schema::hasColumn('form_templates', 'is_active')) {
                $columnsToDrop[] = 'is_active';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
