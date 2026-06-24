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
        Schema::create('queues', function (Blueprint $table) {
           $table->id();
            $table->string('name');
            $table->string('qr_code_string')->unique();
            $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('estimated_time_minutes')->default(10); 
            $table->enum('status', ['active', 'paused', 'closed'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queues');
    }
};
