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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->enum('ownership', ['citizen', 'government', 'private'])->default('citizen');
            $table->enum('type', ['commercial', 'residential', 'land', 'industrial'])->default('residential');
            $table->text('address_details');
            $table->foreignId('pin_id')->nullable()->constrained('map_pins')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
