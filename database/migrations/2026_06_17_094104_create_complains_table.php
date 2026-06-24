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
        Schema::create('complains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->enum('type', ['individual', 'collective','emergency']);
            $table->foreignId('category_id')->constrained('complain_categories')->cascadeOnDelete();
            $table->enum('status', ['pending', 'under_review', 'resolved', 'rejected'])->default('pending');
            $table->integer('priority_score')->default(0);
            $table->foreignId('pin_id')->constrained('map_pins')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complains');
    }
};
