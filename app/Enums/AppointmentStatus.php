<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Booked = 'booked';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Booked => 'Booked',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::NoShow => 'No-show',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Booked => 'bg-brand-blue/10 text-brand-blue',
            self::Completed => 'bg-brand-green/10 text-emerald-700',
            self::Cancelled => 'bg-slate-100 text-slate-500',
            self::NoShow => 'bg-red-100 text-red-600',
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
