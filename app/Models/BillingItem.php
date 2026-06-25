<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One frozen line on a billing statement.
 */
#[Fillable([
    'billing_statement_id', 'appointment_procedure_id', 'description',
    'quantity', 'unit_price', 'line_total',
])]
class BillingItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(BillingStatement::class, 'billing_statement_id');
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(AppointmentProcedure::class, 'appointment_procedure_id');
    }
}
