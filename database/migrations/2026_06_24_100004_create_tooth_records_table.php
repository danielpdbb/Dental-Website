<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Interactive odontogram data — one row per tooth the dentist annotates during
     * an appointment. The patient-record chart reads the latest row per FDI number.
     */
    public function up(): void
    {
        Schema::create('tooth_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_procedure_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedTinyInteger('fdi_number'); // 11–48 (FDI/ISO)
            $table->string('condition')->default('healthy');
            $table->string('treatment_done')->nullable();
            $table->string('medicine_given')->nullable();
            $table->string('special_procedure')->nullable();
            $table->text('observation')->nullable();
            $table->json('surfaces')->nullable(); // ["M","O","D","B","L"]

            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['appointment_id', 'fdi_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tooth_records');
    }
};
