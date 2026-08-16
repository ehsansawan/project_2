<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE projects MODIFY COLUMN status 
            ENUM('planning', 'submitted', 'approved', 'rejected', 'open', 'active', 'completed', 'cancelled') 
            NOT NULL DEFAULT 'planning'");

        if (!Schema::hasColumn('projects', 'rejection_reason')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->text('rejection_reason')->nullable()->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('projects', 'rejection_reason')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('rejection_reason');
            });
        }

        DB::statement("ALTER TABLE projects MODIFY COLUMN status 
            ENUM('planning', 'open', 'active', 'completed', 'cancelled') 
            NOT NULL DEFAULT 'planning'");
    }
};