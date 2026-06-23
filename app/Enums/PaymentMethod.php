<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case Gcash = 'gcash';
    case Insurance = 'insurance';
    case Rewards = 'rewards'; // redeemed rewards points applied as a discount

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Card => 'Card',
            self::Gcash => 'GCash',
            self::Insurance => 'Insurance',
            self::Rewards => 'Rewards credit',
        };
    }

    /**
     * Methods a cashier can manually choose. Excludes Rewards, which is only
     * ever created by redeeming points (never typed in by hand).
     *
     * @return array<string, string>
     */
    public static function manualOptions(): array
    {
        return collect(self::cases())
            ->reject(fn (self $m) => $m === self::Rewards)
            ->mapWithKeys(fn (self $m) => [$m->value => $m->label()])
            ->all();
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $m) => [$m->value => $m->label()])
            ->all();
    }
}
