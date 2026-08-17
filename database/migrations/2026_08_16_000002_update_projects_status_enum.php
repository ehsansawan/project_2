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
        Schema::table('projects', function (Blueprint $table) {
            $table->enum('status', ['planning', 'submitted', 'approved', 'rejected', 'open', 'active', 'completed', 'cancelled'])
                ->default('planning')->change();
        });

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

        Schema::table('projects', function (Blueprint $table) {
            $table->enum('status', ['planning', 'open', 'active', 'completed', 'cancelled'])
                ->default('planning')->change();
        });
    }
};
