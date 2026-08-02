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
        Schema::table('queues', function (Blueprint $table) {
                $table->dropForeign(['employee_id']); // Laravel سيبحث عن queues_employee_id_foreign وسيجده
                $table->dropColumn('employee_id');
            });

        Schema::rename('queues', 'services');
        Schema::rename('virtual_users', 'queue_tickets');

         Schema::table('queue_tickets', function (Blueprint $table) {
            $table->renameColumn('queue_id', 'service_id');
            $table->dropColumn('service_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('queue_tickets', function (Blueprint $table) {
            $table->renameColumn('service_id', 'queue_id');
            $table->string('service_type')->after('user_name');
        });

        Schema::rename('queue_tickets', 'virtual_users');
        Schema::rename('services', 'queues');

        Schema::table('queues', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->after('qr_code_string')->constrained('users')->nullOnDelete();
        });
    }
};
