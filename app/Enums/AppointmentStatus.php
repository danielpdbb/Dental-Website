<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Booked = 'booked';
    case InTreatment = 'in_treatment'; // dentist is recording/performing procedures
    case ForBilling = 'for_billing';   // dentist endorsed → receptionist to bill
    case Billed = 'billed';            // statement created → awaiting payment
    case Completed = 'completed';      // paid & done → moves to treatment history
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Booked => 'Booked',
            self::InTreatment => 'In treatment',
            self::ForBilling => 'For billing',
            self::Billed => 'Billed',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::NoShow => 'No-show',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Booked => 'bg-brand-blue/10 text-brand-blue',
            self::InTreatment => 'bg-indigo-100 text-indigo-700',
            self::ForBilling => 'bg-amber-100 text-amber-700',
            self::Billed => 'bg-purple-100 text-purple-700',
            self::Completed => 'bg-brand-green/10 text-emerald-700',
            self::Cancelled => 'bg-slate-100 text-slate-500',
            self::NoShow => 'bg-red-100 text-red-600',
        };
    }

    /** Statuses that count as a kept/finished visit for analytics. */
    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::NoShow], true);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $s) => [$s->value => $s->label()])
            ->all();
    }
}
