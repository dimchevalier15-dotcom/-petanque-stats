<?php

declare(strict_types=1);

namespace App\Enum;

enum JumpShotSeriesConsequence: string
{
    case DOWN = 'down';
    case STAY = 'stay';
    case UP = 'up';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
