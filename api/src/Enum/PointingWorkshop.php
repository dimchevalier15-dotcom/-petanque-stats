<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * The 5 official workshops of the "point de précision" PLAS exercise (1 to 5).
 */
enum PointingWorkshop: int
{
    case JACK_ALONE = 1;
    case BALL_IN_FRONT_OF_JACK = 2;
    case TWO_BALLS_IN_FRONT_OF_JACK = 3;
    case PUSH_BALL = 4;
    case PUSH_JACK = 5;

    /**
     * @return list<self>
     */
    public static function all(): array
    {
        return self::cases();
    }
}
