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
        if (!Schema::hasTable('requests')) {
            return;
        }

        Schema::table('requests', function (Blueprint $table) {
            if (!Schema::hasColumn('requests', 'staff1_signature_data')) {
                $table->text('staff1_signature_data')->nullable();
            }
            if (!Schema::hasColumn('requests', 'staff1_signed_at')) {
                $table->timestamp('staff1_signed_at')->nullable();
            }
            if (!Schema::hasColumn('requests', 'staff2_signature_data')) {
                $table->text('staff2_signature_data')->nullable();
            }
            if (!Schema::hasColumn('requests', 'staff2_signed_at')) {
                $table->timestamp('staff2_signed_at')->nullable();
            }
            if (!Schema::hasColumn('requests', 'dean_signature_data')) {
                $table->text('dean_signature_data')->nullable();
            }
            if (!Schema::hasColumn('requests', 'dean_signed_at')) {
                $table->timestamp('dean_signed_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('requests')) {
            return;
        }

        Schema::table('requests', function (Blueprint $table) {
            $columns = [];
            foreach ([
                'staff1_signature_data',
                'staff1_signed_at',
                'staff2_signature_data',
                'staff2_signed_at',
                'dean_signature_data',
                'dean_signed_at',
            ] as $column) {
                if (Schema::hasColumn('requests', $column)) {
                    $columns[] = $column;
                }
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
