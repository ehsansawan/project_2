<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->enum('status', ['pending_review', 'approved', 'rejected'])
                ->default('pending_review')->after('target_audience');
            $table->text('rejection_reason')->nullable()->after('status');
            $table->foreignId('reviewed_by')->nullable()->after('rejection_reason')
                ->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->timestamp('published_at')->nullable()->after('reviewed_at');
            $table->foreignId('pin_id')->nullable()->after('published_at')
                ->constrained('map_pins')->onDelete('set null');
        });

        DB::statement("ALTER TABLE news MODIFY COLUMN type ENUM('news','announcement','emergency_alert') NOT NULL DEFAULT 'news'");

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->text('details')->nullable()->after('action');
        });

        DB::statement("ALTER TABLE audit_logs MODIFY COLUMN action ENUM('reject','approve','close','create') NOT NULL");
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pin_id');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['status', 'rejection_reason', 'reviewed_at', 'published_at']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('details');
        });

        DB::statement("ALTER TABLE audit_logs MODIFY COLUMN action ENUM('reject','approve') NOT NULL");
    }
};