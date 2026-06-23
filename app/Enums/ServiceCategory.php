<?php

namespace App\Enums;

/**
 * Groups services for analytics (revenue by category, service-mix pivots) and,
 * later, as a feature for the procedure-recommendation model.
 */
enum ServiceCategory: string
{
    case Preventive = 'preventive';
    case Restorative = 'restorative';
    case Cosmetic = 'cosmetic';
    case Surgical = 'surgical';
    case Orthodontic = 'orthodontic';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Preventive => 'Preventive',
            self::Restorative => 'Restorative',
            self::Cosmetic => 'Cosmetic',
            self::Surgical => 'Surgical',
            self::Orthodontic => 'Orthodontic',
            self::Other => 'Other',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Preventive => 'bg-brand-green/10 text-emerald-700',
            self::Restorative => 'bg-brand-blue/10 text-brand-blue',
            self::Cosmetic => 'bg-purple-100 text-purple-700',
            self::Surgical => 'bg-red-100 text-red-600',
            self::Orthodontic => 'bg-amber-100 text-amber-700',
            self::Other => 'bg-slate-100 text-slate-500',
        };
    }

    /** Hex color for charts (Chart.js datasets). */
    public function color(): string
    {
        return match ($this) {
            self::Preventive => '#10B981',
            self::Restorative => '#3B82F6',
            self::Cosmetic => '#A855F7',
            self::Surgical => '#EF4444',
            self::Orthodontic => '#F59E0B',
            self::Other => '#94A3B8',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }

    /**
     * Best-guess category from a service name (used to backfill existing rows).
     */
    public static function guessFromName(string $name): self
    {
        $n = strtolower($name);

        return match (true) {
            str_contains($n, 'clean') || str_contains($n, 'check') || str_contains($n, 'fluoride') || str_contains($n, 'sealant') => self::Preventive,
            str_contains($n, 'whiten') || str_contains($n, 'veneer') || str_contains($n, 'cosmetic') => self::Cosmetic,
            str_contains($n, 'extract') || str_contains($n, 'implant') || str_contains($n, 'surg') => self::Surgical,
            str_contains($n, 'brace') || str_contains($n, 'ortho') || str_contains($n, 'align') => self::Orthodontic,
            str_contains($n, 'fill') || str_contains($n, 'canal') || str_contains($n, 'crown') || str_contains($n, 'bridge') || str_contains($n, 'restor') => self::Restorative,
            default => self::Other,
        };
    }
}
