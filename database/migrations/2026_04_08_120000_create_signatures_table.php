<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
            $table->string('role', 50);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('signature_path');
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->unique(['request_id', 'role']);
            $table->index(['role', 'signed_at']);
        });

        // Backfill existing request-level stage signatures into the new normalized table.
        $now = now();
        $rows = DB::table('requests')->select([
            'id',
            'recommended_by',
            'staff2_signature_data',
            'staff2_signed_at',
            'dean_approved_by',
            'dean_signature_data',
            'dean_signed_at',
        ])->get();

        $inserts = [];
        foreach ($rows as $row) {
            if (!empty($row->staff2_signature_data)) {
                $inserts[] = [
                    'request_id' => $row->id,
                    'role' => 'staff2',
                    'user_id' => $row->recommended_by,
                    'signature_path' => $row->staff2_signature_data,
                    'signed_at' => $row->staff2_signed_at,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($row->dean_signature_data)) {
                $inserts[] = [
                    'request_id' => $row->id,
                    'role' => 'dean',
                    'user_id' => $row->dean_approved_by,
                    'signature_path' => $row->dean_signature_data,
                    'signed_at' => $row->dean_signed_at,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($inserts)) {
            DB::table('signatures')->insert($inserts);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('signatures');
    }
};
