<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * One store-wide setting (key → JSON-encoded value). Managed from the Availability
 * page (management only) and overlaid onto config('clinic.*') at boot.
 */
#[Fillable(['key', 'value'])]
class ClinicSetting extends Model
{
    private const CACHE_KEY = 'clinic_settings.all';

    /** All settings as key => decoded value (cached until the next set()). */
    public static function all_(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => self::query()
            ->pluck('value', 'key')
            ->map(fn ($v) => json_decode($v, true))
            ->all());
    }

    public static function set(string $key, mixed $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => json_encode($value)]);
        Cache::forget(self::CACHE_KEY);
    }
}
