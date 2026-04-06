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
        if (!Schema::hasTable('request_types') || !Schema::hasTable('form_templates')) {
            return;
        }

        if (Schema::hasColumn('request_types', 'default_template_id')) {
            return;
        }

        Schema::table('request_types', function (Blueprint $table) {
            $table->foreignId('default_template_id')->nullable()->constrained('form_templates')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('request_types') || !Schema::hasColumn('request_types', 'default_template_id')) {
            return;
        }

        Schema::table('request_types', function (Blueprint $table) {
            $table->dropForeign(['default_template_id']);
            $table->dropColumn('default_template_id');
        });
    }
};
