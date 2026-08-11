<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * The 5 official workshops (ateliers) of the "tir de précision" discipline.
 * The backing value is the official workshop order (1 to 5).
 */
enum ShootingWorkshop: int
{
    case BALL_ALONE = 1;
    case BALL_BEHIND_JACK = 2;
    case BETWEEN_TWO_BALLS = 3;
    case JUMPED_BALL = 4;
    case JACK = 5;

    /**
     * @return list<self>
     */
    public static function all(): array
    {
        return self::cases();
    }
}
