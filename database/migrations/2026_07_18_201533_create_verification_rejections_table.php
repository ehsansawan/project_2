<?php

use App\Enums\RejectionReason;
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
        Schema::create('verification_rejections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();// admin who reject
            $table->foreignId('verification_request_id')
                ->constrained('verification_requests')->cascadeOnDelete();
//            $table->enum('reason', array_column(RejectionReason::cases(), 'value'));
            $table->json('reason')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_rejections');
    }
};
