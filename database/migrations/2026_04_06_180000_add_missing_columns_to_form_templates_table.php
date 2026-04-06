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
            if (!Schema::hasColumn('form_templates', 'template_type')) {
                $table->string('template_type')->default('general_form')->after('title');
            }

            if (!Schema::hasColumn('form_templates', 'field_mappings')) {
                $table->json('field_mappings')->nullable()->after('file_path');
            }

            if (!Schema::hasColumn('form_templates', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('field_mappings');
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
            $columns = [];

            foreach (['template_type', 'field_mappings', 'is_active'] as $column) {
                if (Schema::hasColumn('form_templates', $column)) {
                    $columns[] = $column;
                }
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
