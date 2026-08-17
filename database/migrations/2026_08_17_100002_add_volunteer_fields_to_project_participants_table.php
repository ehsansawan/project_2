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
        Schema::table('project_participants', function (Blueprint $table) {
            $table->string('whatsapp_number')->nullable()->after('user_id');
            $table->foreignId('project_requirement_id')->nullable()->after('whatsapp_number')
                ->constrained('project_requirements')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->after('status')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->after('approved_by')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('rejected_by');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_participants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_requirement_id');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn(['whatsapp_number', 'approved_at', 'rejected_at']);
        });
    }
};
