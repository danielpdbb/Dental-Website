<?php

namespace App\Enums;

/**
 * Lifecycle of an appointment recommendation as the dentist reviews it:
 * suggested by the model → accepted (optionally after editing) or rejected.
 */
enum AdviceStatus: string
{
    case Suggested = 'suggested';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Suggested => 'Suggested',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Suggested => 'bg-amber-100 text-amber-700',
            self::Accepted => 'bg-brand-green/10 text-emerald-700',
            self::Rejected => 'bg-slate-100 text-slate-400',
        };
    }
}
