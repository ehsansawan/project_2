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
        Schema::table('complains', function (Blueprint $table) {
             $table->enum('status', ['submitted ', 'under_review',
              'published', 'rejected', 'closed'])->default('submitted')->change();  
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('complains', function (Blueprint $table) {
             $table->enum('status', ['pending', 'under_review', 'resolved', 'rejected'])->default('pending');
        });
    }
};
