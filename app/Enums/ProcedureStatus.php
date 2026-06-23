<?php

namespace App\Enums;

/**
 * Status of a single procedure line item within an appointment ("current treatment").
 * planned → the dentist intends to do it; performed → it's been done this visit.
 */
enum ProcedureStatus: string
{
    case Planned = 'planned';
    case Performed = 'performed';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planned',
            self::Performed => 'Performed',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Planned => 'bg-slate-100 text-slate-500',
            self::Performed => 'bg-brand-green/10 text-emerald-700',
        };
    }
}
