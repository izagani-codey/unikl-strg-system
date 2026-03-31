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
        Schema::table('request_types', function (Blueprint $table) {
            $table->text('description')->nullable()->after('slug');
            $table->timestamp('created_at')->nullable()->after('description');
            $table->timestamp('updated_at')->nullable()->after('created_at');
        });
        
        // Update existing records to have current timestamps
        \DB::table('request_types')->whereNull('created_at')->update([
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_types', function (Blueprint $table) {
            $table->dropColumn(['description', 'created_at', 'updated_at']);
        });
    }
};
