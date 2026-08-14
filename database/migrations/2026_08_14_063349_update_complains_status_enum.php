<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE complains MODIFY COLUMN status 
            ENUM('under_review', 'published', 'rejected', 'in_progress', 'closed') 
            NOT NULL DEFAULT 'under_review'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE complains MODIFY COLUMN status 
            ENUM('submitted', 'under_review', 'published', 'rejected', 'closed') 
            NOT NULL DEFAULT 'submitted'");
    }
};
