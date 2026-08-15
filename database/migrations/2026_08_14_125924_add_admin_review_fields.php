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
        Schema::table('complains', function (Blueprint $table) {
            $table->text('decision_reason')->nullable()->after('status');
        });

        DB::statement("ALTER TABLE audit_logs MODIFY COLUMN action ENUM('reject','approve','close') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('complains', function (Blueprint $table) {
            $table->dropColumn('decision_reason');
        });

        DB::statement("ALTER TABLE audit_logs MODIFY COLUMN action ENUM('reject','approve') NOT NULL");
    }
};
