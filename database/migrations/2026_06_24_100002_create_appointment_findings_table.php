<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stage-2 dentist clinical findings — one per appointment. Captured after the
     * procedure, these are the dentist's actual observations and feed the more
     * accurate next-visit recommendation.
     */
    public function up(): void
    {
        Schema::create('appointment_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained()->cascadeOnDelete();

            $table->boolean('cavity_found')->default(false);
            $table->string('gum_inflammation')->default('none');  // none | mild | moderate | severe
            $table->string('plaque_level')->default('low');       // low | medium | high
            $table->boolean('tooth_mobility')->default(false);
            $table->string('infection_signs')->default('none');   // none | possible | present
            $table->boolean('xray_needed')->default(false);

            // Optional observed counts that refine the regression vector.
            $table->unsignedTinyInteger('missing_teeth')->nullable();
            $table->unsignedTinyInteger('existing_fillings')->nullable();

            $table->string('treatment_done_today')->nullable();
            $table->text('remarks')->nullable();

            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_findings');
    }
};
