<?php

namespace App\Enums;

/**
 * Which regression stage produced an appointment recommendation:
 *  - Stage1Current: the patient's pre-appointment assessment → possible current treatment.
 *  - Stage2Next:    the dentist's clinical findings → recommended next visit.
 */
enum RecommendationSource: string
{
    case Stage1Current = 'stage1_current';
    case Stage2Next = 'stage2_next';

    public function label(): string
    {
        return match ($this) {
            self::Stage1Current => 'Possible current treatment',
            self::Stage2Next => 'Recommended next visit',
        };
    }
}
