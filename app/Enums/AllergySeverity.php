<?php

namespace App\Enums;

enum AllergySeverity: string
{
    case Mild = 'mild';
    case Moderate = 'moderate';
    case Severe = 'severe';

    public function label(): string
    {
        return match ($this) {
            self::Mild => 'Mild',
            self::Moderate => 'Moderate',
            self::Severe => 'Severe',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Mild => 'bg-slate-100 text-slate-600',
            self::Moderate => 'bg-amber-100 text-amber-700',
            self::Severe => 'bg-red-100 text-red-600',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $s) => [$s->value => $s->label()])
            ->all();
    }
}
