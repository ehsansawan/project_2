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
        Schema::table('project_votes', function (Blueprint $table) {
            $table->boolean('value')->default(true)->after('user_id');
            $table->integer('citizenship_score_at_vote_time')->after('value');
            $table->decimal('vote_weight', 8, 4)->after('citizenship_score_at_vote_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_votes', function (Blueprint $table) {
            $table->dropColumn(['value', 'citizenship_score_at_vote_time', 'vote_weight']);
        });
    }
};
