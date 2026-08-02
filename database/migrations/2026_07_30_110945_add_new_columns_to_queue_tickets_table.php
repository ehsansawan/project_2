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
           Schema::table('queue_tickets', function (Blueprint $table) {
            $table->boolean('is_manual')->default(false)->after('status');
            $table->foreignId('served_by_employee_id')->nullable()->after('is_manual')->constrained('users')->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable()->after('served_by_employee_id');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('queue_tickets', function (Blueprint $table) {
            $table->dropForeign(['served_by_employee_id']);
            $table->dropColumn(['is_manual', 'served_by_employee_id', 'latitude', 'longitude']);
        });
    }
};
