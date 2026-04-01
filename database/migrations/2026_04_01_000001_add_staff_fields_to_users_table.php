<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('staff_id')->nullable()->unique()->after('role');
            $table->string('designation')->nullable()->after('staff_id');
            $table->string('department')->nullable()->after('designation');
            $table->string('phone')->nullable()->after('department');
            $table->string('employee_level')->nullable()->after('phone');
            $table->text('signature_data')->nullable()->after('employee_level'); // base64 stored signature
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['staff_id', 'designation', 'department', 'phone', 'employee_level', 'signature_data']);
        });
    }
};
