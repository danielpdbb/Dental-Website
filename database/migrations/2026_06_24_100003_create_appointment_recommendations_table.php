<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-appointment regression output. A Stage-1 row is the possible current
     * treatment; a Stage-2 row is the recommended next visit the dentist verifies,
     * accepts/edits/rejects, prints, and sends to the patient.
     */
    public function up(): void
    {
        Schema::create('appointment_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->string('source')->index(); // stage1_current | stage2_next

            $table->text('recommendation');
            $table->foreignId('linked_service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->decimal('confidence', 5, 4)->nullable(); // regression score 0–1
            $table->string('priority')->nullable();          // low | medium | high
            $table->unsignedTinyInteger('follow_up_weeks')->nullable();
            $table->timestamp('suggested_at')->nullable();   // Decision-Tree follow-up date/time

            $table->string('status')->default('suggested')->index(); // suggested | accepted | rejected
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('sent_to_patient_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_recommendations');
    }
};
