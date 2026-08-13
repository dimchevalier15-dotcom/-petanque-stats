<?php

declare(strict_types=1);

namespace App\Enum;

enum TrainingPointResult: string
{
    case PERFECT = 'perfect';
    case VERY_GOOD = 'very_good';
    case GOOD = 'good';
    case ACCEPTABLE = 'acceptable';
    case BAD = 'bad';
    case USELESS = 'useless';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $r) => $r->value, self::cases());
    }

    /**
     * Results counted as successful for the success-rate KPI.
     *
     * @return list<string>
     */
    public static function successfulValues(): array
    {
        return [
            self::PERFECT->value,
            self::VERY_GOOD->value,
            self::GOOD->value,
        ];
    }
}
