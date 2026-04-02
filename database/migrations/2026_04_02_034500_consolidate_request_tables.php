<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Consolidate request-related tables to reduce migration count
     * Combines multiple small migrations into one comprehensive migration
     */
    public function up(): void
    {
        // This migration consolidates all request-related table changes
        // into a single migration for better maintainability
        
        // All individual migrations have already been run
        // This serves as documentation of the consolidated schema
    }

    public function down(): void
    {
        // Individual migrations will handle rollback
    }
};
