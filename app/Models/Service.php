<?php

namespace App\Models;

use App\Enums\ServiceCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'category', 'description', 'duration_minutes', 'price', 'is_active'])]
class Service extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'category' => ServiceCategory::class,
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'duration_minutes' => 'integer',
        ];
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
