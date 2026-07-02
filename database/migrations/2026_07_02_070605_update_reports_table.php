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
        Schema::table('reports', function (Blueprint $table) {
            $table->enum('status',['pending','approved','rejected'])->default('pending')->change();

            $table->text('decision_reason')->nullable()->after('status');

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->after('decision_reason');

            $table->unique(['user_id','complain_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->enum('status', ['pending', 'reviewed', 'resolved'])->default('pending')->change();
            $table->dropColumn(['decision_reason', 'reviewed_by']);
            $table->dropUnique(['user_id', 'complain_id']);
        });
    }
};
