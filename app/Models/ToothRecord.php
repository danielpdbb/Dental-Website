<?php

namespace App\Models;

use App\Enums\ToothCondition;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One tooth annotation made on the interactive odontogram during an appointment.
 */
#[Fillable([
    'appointment_id', 'appointment_procedure_id', 'fdi_number', 'condition',
    'treatment_done', 'medicine_given', 'special_procedure', 'observation',
    'surfaces', 'recorded_by',
])]
class ToothRecord extends Model
{
    protected function casts(): array
    {
        return [
            'fdi_number' => 'integer',
            'condition' => ToothCondition::class,
            'surfaces' => 'array',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(AppointmentProcedure::class, 'appointment_procedure_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Universal (1–32) tooth number for the given FDI/ISO number, so the chart can
     * show both systems. Returns null for numbers outside the permanent dentition.
     */
    public static function fdiToUniversal(int $fdi): ?int
    {
        return self::FDI_UNIVERSAL[$fdi] ?? null;
    }

    /**
     * Shape a set of tooth records into the FDI-keyed array the chart component reads.
     * When records repeat a tooth, the most recent one wins.
     *
     * @param  \Illuminate\Support\Collection<int, self>  $records
     * @return array<int, array<string, mixed>>
     */
    public static function chartArray(\Illuminate\Support\Collection $records): array
    {
        return $records->sortBy('created_at')->keyBy('fdi_number')->map(fn (self $r) => [
            'uni' => self::fdiToUniversal($r->fdi_number),
            'condition' => $r->condition->value,
            'label' => $r->condition->label(),
            'color' => $r->condition->color(),
            'treatment_done' => $r->treatment_done,
            'medicine_given' => $r->medicine_given,
            'special_procedure' => $r->special_procedure,
            'observation' => $r->observation,
            'surfaces' => $r->surfaces ?? [],
            'date' => $r->created_at?->format('M j, Y'),
            'dentist' => $r->recorder?->name,
        ])->all();
    }

    /**
     * Group records into a per-tooth timeline (most recent first) for the modal.
     *
     * @param  \Illuminate\Support\Collection<int, self>  $records
     * @return array<int, list<array<string, mixed>>>
     */
    public static function historyArray(\Illuminate\Support\Collection $records): array
    {
        return $records->sortByDesc('created_at')->groupBy('fdi_number')->map(
            fn ($group) => $group->map(fn (self $r) => [
                'date' => $r->created_at?->format('M j, Y'),
                'label' => $r->condition->label(),
                'color' => $r->condition->color(),
                'treatment_done' => $r->treatment_done,
                'medicine_given' => $r->medicine_given,
                'special_procedure' => $r->special_procedure,
                'observation' => $r->observation,
                'dentist' => $r->recorder?->name,
            ])->values()->all()
        )->all();
    }

    /** FDI → Universal lookup for the 32 permanent teeth. */
    public const FDI_UNIVERSAL = [
        18 => 1, 17 => 2, 16 => 3, 15 => 4, 14 => 5, 13 => 6, 12 => 7, 11 => 8,
        21 => 9, 22 => 10, 23 => 11, 24 => 12, 25 => 13, 26 => 14, 27 => 15, 28 => 16,
        38 => 17, 37 => 18, 36 => 19, 35 => 20, 34 => 21, 33 => 22, 32 => 23, 31 => 24,
        41 => 25, 42 => 26, 43 => 27, 44 => 28, 45 => 29, 46 => 30, 47 => 31, 48 => 32,
    ];
}
