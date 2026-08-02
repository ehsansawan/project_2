<?php

use App\Enums\AuditAction;
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
        Schema::create('audit_logs', function (Blueprint $table) {

            $table->id();

            // Admin who made the decision
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Polymorphic relation
            $table->morphs('auditable');

            // Action
            $table->enum('action', array_column(AuditAction::cases(), 'value'));

            // Rejection information
//            $table->enum('reason', array_column(RejectionReason::cases(), 'value'))
//                ->nullable();

//            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
