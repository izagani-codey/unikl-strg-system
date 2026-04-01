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
            $table->unsignedBigInteger('dean_approved_by')->nullable()->after('recommended_by');
            $table->datetime('dean_approved_at')->nullable()->after('dean_approved_by');
            $table->text('dean_notes')->nullable()->after('dean_approved_at');
            $table->text('dean_rejection_reason')->nullable()->after('dean_notes');
            
            $table->foreign('dean_approved_by')->references('id')->on('users')->onDelete('set null');
            $table->index('dean_approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['dean_approved_by']);
            $table->dropIndex(['dean_approved_by']);
            $table->dropColumn([
                'dean_approved_by',
                'dean_approved_at', 
                'dean_notes',
                'dean_rejection_reason'
            ]);
        });
    }
};
