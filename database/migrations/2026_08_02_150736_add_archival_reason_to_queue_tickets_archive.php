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
        Schema::table('queue_tickets_archive', function (Blueprint $table) {
            $table->enum('archival_reason', ['served', 'visitor_absent', 'end_of_day'])
                  ->default('served')
                  ->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('queue_tickets_archive', function (Blueprint $table) {
            $table->dropColumn('archival_reason');
        });
    }
};
