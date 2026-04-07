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
        Schema::table('audit_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_logs', 'actor_role')) {
                $table->string('actor_role')->after('actor_id');
            }
            if (!Schema::hasColumn('audit_logs', 'action')) {
                $table->string('action')->after('actor_role');
            }
            if (!Schema::hasColumn('audit_logs', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('note');
            }
            if (!Schema::hasColumn('audit_logs', 'is_override')) {
                $table->boolean('is_override')->default(false)->after('rejection_reason');
            }
            if (!Schema::hasColumn('audit_logs', 'signature_data')) {
                $table->string('signature_data')->nullable()->after('is_override');
            }
            if (!Schema::hasColumn('audit_logs', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('signature_data');
            }
            if (!Schema::hasColumn('audit_logs', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }
            if (!Schema::hasColumn('audit_logs', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            //
        });
    }
};
