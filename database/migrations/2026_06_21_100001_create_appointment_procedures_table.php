<?php

use App\Enums\AppointmentStatus;
use App\Enums\ProcedureStatus;
use App\Models\Appointment;
use App\Models\AppointmentProcedure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('procedure_name');
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('duration_minutes')->default(30);
            $table->string('status')->default(ProcedureStatus::Planned->value)->index();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Backfill: turn every existing single-service appointment into one line item.
        Appointment::with('service')->chunkById(200, function ($appointments) {
            foreach ($appointments as $a) {
                $done = $a->status === AppointmentStatus::Completed;

                AppointmentProcedure::create([
                    'appointment_id' => $a->id,
                    'service_id' => $a->service_id,
                    'procedure_name' => $a->service?->name ?? 'Procedure',
                    'price' => $a->total_amount > 0 ? $a->total_amount : ($a->service?->price ?? 0),
                    'duration_minutes' => $a->duration_minutes,
                    'status' => $done ? ProcedureStatus::Performed : ProcedureStatus::Planned,
                    'performed_by' => $done ? $a->dentist_id : null,
                    'performed_at' => $done ? $a->scheduled_at : null,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_procedures');
    }
};
