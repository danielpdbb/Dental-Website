<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customisable dentist availability, replacing the hardcoded clinic-wide 9–5:
 *  - weekly rules  (weekday set, date NULL): recurring hours or a recurring day off
 *  - date overrides (date set, weekday NULL): block a specific day, or custom hours
 *    for that day (e.g. "morning only on Jul 15")
 * Absent any rule, the clinic's default hours (config/clinic.php) apply.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dentist_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dentist_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday')->nullable(); // 1 (Mon) – 7 (Sun); null = date override
            $table->date('date')->nullable();                   // specific-day override; null = weekly rule
            $table->boolean('is_off')->default(false);          // fully unavailable
            $table->time('start_time')->nullable();             // custom window (when not off)
            $table->time('end_time')->nullable();
            $table->string('note')->nullable();                 // e.g. "Seminar", "Half-day"
            $table->timestamps();

            $table->index(['dentist_id', 'weekday']);
            $table->index(['dentist_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dentist_schedules');
    }
};
