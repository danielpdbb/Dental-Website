<?php

namespace App\Enums;

/**
 * The clinical state of a single tooth as recorded on the interactive odontogram.
 * Each case carries a fill colour so the teeth chart can render at a glance, and a
 * badge style for the per-tooth modal/legend.
 */
enum ToothCondition: string
{
    case Healthy = 'healthy';
    case Caries = 'caries';
    case Filled = 'filled';
    case Crown = 'crown';
    case RootCanal = 'root_canal';
    case Bridge = 'bridge';
    case Implant = 'implant';
    case Sealant = 'sealant';
    case Missing = 'missing';
    case Extracted = 'extracted';

    public function label(): string
    {
        return match ($this) {
            self::Healthy => 'Healthy',
            self::Caries => 'Caries / Decay',
            self::Filled => 'Filled',
            self::Crown => 'Crown',
            self::RootCanal => 'Root canal',
            self::Bridge => 'Bridge',
            self::Implant => 'Implant',
            self::Sealant => 'Sealant',
            self::Missing => 'Missing',
            self::Extracted => 'Extracted',
        };
    }

    /** Fill colour used to paint the tooth on the SVG chart. */
    public function color(): string
    {
        return match ($this) {
            self::Healthy => '#FFFFFF',
            self::Caries => '#EF4444',
            self::Filled => '#3B82F6',
            self::Crown => '#F59E0B',
            self::RootCanal => '#8B5CF6',
            self::Bridge => '#14B8A6',
            self::Implant => '#6366F1',
            self::Sealant => '#22C55E',
            self::Missing => '#CBD5E1',
            self::Extracted => '#64748B',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Healthy => 'bg-slate-100 text-slate-500',
            self::Caries => 'bg-red-100 text-red-600',
            self::Filled => 'bg-brand-blue/10 text-brand-blue',
            self::Crown => 'bg-amber-100 text-amber-700',
            self::RootCanal => 'bg-purple-100 text-purple-700',
            self::Bridge => 'bg-teal-100 text-teal-700',
            self::Implant => 'bg-indigo-100 text-indigo-700',
            self::Sealant => 'bg-brand-green/10 text-emerald-700',
            self::Missing => 'bg-slate-100 text-slate-400',
            self::Extracted => 'bg-slate-200 text-slate-600',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}
