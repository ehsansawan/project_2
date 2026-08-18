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
        // decimal(10,2) maxed out at ~99.9 million - decimal(20,2) allows
        // values up into the quintillions, comfortably covering trillions.
        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('budget', 20, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('budget', 10, 2)->nullable()->change();
        });
    }
};
