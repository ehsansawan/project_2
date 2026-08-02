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
         Schema::create('queue_tickets_archive', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_ticket_id');
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->string('user_name');
            $table->integer('current_number');
            $table->enum('status', ['completed', 'no_show']);
            $table->boolean('is_manual')->default(false);
            $table->timestamp('joined_at');
            $table->timestamp('called_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->foreignId('served_by_employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_tickets_archive');
    }
};
