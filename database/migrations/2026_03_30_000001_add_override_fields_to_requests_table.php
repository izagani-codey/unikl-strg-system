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
        Schema::table('requests', function (Blueprint $table) {
            $table->boolean('is_overridden')->default(false)->after('status_id');
            $table->foreignId('overridden_by')->nullable()->constrained('users')->after('recommended_by');
            $table->text('override_reason')->nullable()->after('overridden_by');
            $table->timestamp('overridden_at')->nullable()->after('override_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['overridden_by']);
            $table->dropColumn(['is_overridden', 'overridden_by', 'override_reason', 'overridden_at']);
        });
    }
};
