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
        Schema::table('users', function (Blueprint $table) {
            // Add override fields
            if (!Schema::hasColumn('users', 'override_enabled')) {
                $table->boolean('override_enabled')->default(false)->after('employee_level');
            }
            if (!Schema::hasColumn('users', 'override_enabled_at')) {
                $table->timestamp('override_enabled_at')->nullable()->after('override_enabled');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['override_enabled', 'override_enabled_at']);
        });
    }
};
