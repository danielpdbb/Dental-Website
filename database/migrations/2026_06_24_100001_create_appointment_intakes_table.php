<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stage-1 pre-appointment assessment — one per appointment. These are the
     * patient-reportable symptoms/behaviours that feed the procedure-recommendation
     * regression (clinician-observed fields come from findings / the patient record).
     */
    public function up(): void
    {
        Schema::create('appointment_intakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('main_concern')->nullable();
            $table->unsignedTinyInteger('pain_level')->default(0); // 0–10

            // Symptoms
            $table->boolean('toothache')->default(false);
            $table->boolean('sensitivity')->default(false);
            $table->boolean('bleeding_gums')->default(false);
            $table->boolean('bad_breath')->default(false);
            $table->boolean('swelling')->default(false);

            // Oral-health behaviours
            $table->unsignedTinyInteger('brushing_per_day')->default(2);
            $table->boolean('flosses')->default(false);
            $table->boolean('smoker')->default(false);
            $table->string('sugar_level')->default('medium'); // low | medium | high
            $table->unsignedSmallInteger('months_since_cleaning')->default(6);
            $table->string('last_visit_bucket')->nullable(); // e.g. "more_than_1y"

            $table->text('notes')->nullable();
            // Who filled it — a patient (self-service) or a staff member (walk-in/call).
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_intakes');
    }
};
