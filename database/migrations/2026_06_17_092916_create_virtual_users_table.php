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
        Schema::create('virtual_users', function (Blueprint $table) {
            $table->id();
            $table->string('user_name');
            $table->string('service_type'); 
            $table->integer('current_number'); 
            
           
            $table->foreignId('queue_id')->constrained('queues')->cascadeOnDelete();
            
         
            $table->enum('status', ['waiting', 'serving', 'completed', 'no_show'])->default('waiting');
            
            $table->timestamp('joined_at')->useCurrent(); 
            $table->timestamp('called_at')->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('virtual_users');
    }
};
