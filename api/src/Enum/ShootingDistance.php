<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * The 4 official distances (in meters) shot at each workshop.
 */
enum ShootingDistance: int
{
    case SIX_METERS = 6;
    case SEVEN_METERS = 7;
    case EIGHT_METERS = 8;
    case NINE_METERS = 9;

    /**
     * @return list<self>
     */
    public static function all(): array
    {
        return self::cases();
    }
}
