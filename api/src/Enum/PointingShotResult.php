<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Result tier for a single pointing shot. Point values (0 / 3 / 9 / 15) are
 * defined in PointingScoreCalculator; labels depend on the workshop in the UI.
 */
enum PointingShotResult: string
{
    case MISSED = 'missed';
    case P3 = 'p3';
    case P9 = 'p9';
    case P15 = 'p15';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $r) => $r->value, self::cases());
    }
}
