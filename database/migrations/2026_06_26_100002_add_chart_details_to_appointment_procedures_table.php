<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chart-matching detail on a procedure line so that marking it performed can populate
 * the patient's dental chart automatically. All optional (tooth itself is optional).
 * The procedure's `notes` doubles as the tooth observation; treatment_done = name.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_procedures', function (Blueprint $table) {
            $table->string('tooth_condition')->nullable()->after('tooth_fdi');
            $table->string('medicine_given')->nullable()->after('tooth_condition');
            $table->json('tooth_surfaces')->nullable()->after('medicine_given');
        });
    }

    public function down(): void
    {
        Schema::table('appointment_procedures', function (Blueprint $table) {
            $table->dropColumn(['tooth_condition', 'medicine_given', 'tooth_surfaces']);
        });
    }
};
