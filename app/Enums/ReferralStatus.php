<?php

namespace App\Enums;

enum ReferralStatus: string
{
    case Requested = 'requested';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Requested',
            self::InProgress => 'In progress',
            self::Completed => 'Completed',
            self::Declined => 'Declined',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Requested => 'bg-amber-100 text-amber-700',
            self::InProgress => 'bg-brand-blue/10 text-brand-blue',
            self::Completed => 'bg-brand-green/10 text-emerald-700',
            self::Declined => 'bg-red-100 text-red-600',
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
