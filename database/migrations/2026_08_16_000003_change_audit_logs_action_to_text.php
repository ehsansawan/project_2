<?php

use App\Enums\AuditAction;
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
        Schema::table('audit_logs', function (Blueprint $table) {
            // تغيير عمود action من enum إلى text
            $table->string('action')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // إرجاع العمود إلى enum بقيم AuditAction الحالية
            $table->enum('action', array_column(AuditAction::cases(), 'value'))->change();
        });
    }
};