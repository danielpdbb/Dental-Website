<?php

namespace App\Enums;

/**
 * Each row in the rewards ledger has a type. Positive types add points,
 * negative types remove them; the balance is just the sum of every row.
 */
enum RewardTransactionType: string
{
    case Earned = 'earned';       // +  referrer reward for a successful referral
    case Welcome = 'welcome';     // +  new patient's welcome bonus
    case Adjusted = 'adjusted';   // ±  manual staff adjustment / promo
    case Redeemed = 'redeemed';   // −  spent as a discount on a bill
    case Expired = 'expired';     // −  lapsed after inactivity

    public function label(): string
    {
        return match ($this) {
            self::Earned => 'Referral reward',
            self::Welcome => 'Welcome bonus',
            self::Adjusted => 'Adjustment',
            self::Redeemed => 'Redeemed',
            self::Expired => 'Expired',
        };
    }

    /**
     * Whether this type adds (true) or removes (false) points — used for styling.
     */
    public function isCredit(): bool
    {
        return in_array($this, [self::Earned, self::Welcome], true);
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Earned, self::Welcome => 'bg-brand-green/10 text-emerald-700',
            self::Adjusted => 'bg-brand-blue/10 text-brand-blue',
            self::Redeemed => 'bg-amber-100 text-amber-700',
            self::Expired => 'bg-slate-100 text-slate-500',
        };
    }
}
