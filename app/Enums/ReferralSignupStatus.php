<?php

namespace App\Enums;

/**
 * Lifecycle of a "refer a friend" sign-up (distinct from the medical
 * specialist Referral). A new patient signs up with someone's code (Pending),
 * then it becomes Rewarded once they complete a qualifying visit.
 */
enum ReferralSignupStatus: string
{
    case Pending = 'pending';     // referred patient signed up, hasn't qualified yet
    case Rewarded = 'rewarded';   // qualifying visit done — both sides earned points
    case Expired = 'expired';     // never qualified within the allowed window
    case Cancelled = 'cancelled'; // voided (e.g. account removed, abuse)

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending first visit',
            self::Rewarded => 'Rewarded',
            self::Expired => 'Expired',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-700',
            self::Rewarded => 'bg-brand-green/10 text-emerald-700',
            self::Expired => 'bg-slate-100 text-slate-500',
            self::Cancelled => 'bg-red-100 text-red-600',
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
