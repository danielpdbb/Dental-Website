<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional tooth reference on a procedure line. Many procedures are tooth-specific
 * (filling, extraction, root canal, crown), but some are whole-mouth (cleaning,
 * whitening) — so this is nullable and never required.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_procedures', function (Blueprint $table) {
            $table->unsignedTinyInteger('tooth_fdi')->nullable()->after('procedure_name');
        });
    }

    public function down(): void
    {
        Schema::table('appointment_procedures', function (Blueprint $table) {
            $table->dropColumn('tooth_fdi');
        });
    }
};
