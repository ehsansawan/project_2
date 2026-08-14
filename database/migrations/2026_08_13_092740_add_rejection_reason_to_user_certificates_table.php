<?php

use App\Enums\CertificateRejectionReason;
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
        Schema::table('user_certificates', function (Blueprint $table) {
            //
            // add_reason_to_user_certificates_table.php
            $table->enum('rejection_reason', array_column(CertificateRejectionReason::cases(), 'value'))
                ->nullable()
                ->after('status');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_certificates', function (Blueprint $table) {
            //
        });
    }
};
