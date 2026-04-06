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
            $table->text('staff1_signature_data')->nullable();
            $table->timestamp('staff1_signed_at')->nullable();
            $table->text('staff2_signature_data')->nullable();
            $table->timestamp('staff2_signed_at')->nullable();
            $table->text('dean_signature_data')->nullable();
            $table->timestamp('dean_signed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn([
                'staff1_signature_data',
                'staff1_signed_at',
                'staff2_signature_data',
                'staff2_signed_at',
                'dean_signature_data',
                'dean_signed_at'
            ]);
        });
    }
};
