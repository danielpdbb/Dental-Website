<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One clinical-intake record per patient — the symptoms, oral-health behaviors and
     * clinical indicators that feed the procedure-recommendation regression model.
     */
    public function up(): void
    {
        Schema::create('clinical_intakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->unique()->constrained()->cascadeOnDelete();

            // Symptoms
            $table->boolean('toothache')->default(false);
            $table->boolean('sensitivity')->default(false);
            $table->boolean('bleeding_gums')->default(false);
            $table->boolean('bad_breath')->default(false);
            $table->boolean('swelling')->default(false);

            // Oral-health behaviors
            $table->unsignedTinyInteger('brushing_per_day')->default(2);
            $table->boolean('flosses')->default(false);
            $table->boolean('smoker')->default(false);
            $table->string('sugar_level')->default('medium'); // low | medium | high
            $table->unsignedSmallInteger('months_since_cleaning')->default(6);

            // Clinical indicators (dentist-observed)
            $table->boolean('visible_plaque')->default(false);
            $table->boolean('decay_observed')->default(false);
            $table->string('gum_condition')->default('healthy'); // healthy | gingivitis | periodontitis
            $table->unsignedTinyInteger('missing_teeth')->default(0);
            $table->unsignedTinyInteger('existing_fillings')->default(0);

            $table->text('notes')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_intakes');
    }
};
