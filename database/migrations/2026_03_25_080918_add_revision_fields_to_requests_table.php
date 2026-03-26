<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::table('requests', function (Blueprint $table) {
        $table->integer('revision_count')->default(0)->after('rejection_reason');
        $table->date('deadline')->nullable()->after('revision_count');
        $table->boolean('is_priority')->default(false)->after('deadline');
    });
}

public function down(): void
{
    Schema::table('requests', function (Blueprint $table) {
        $table->dropColumn(['revision_count', 'deadline', 'is_priority']);
    });
}
};
