<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Result categories of a single precision shot, per the official scale.
 * Point values depend on the workshop: see ShootingScoreCalculator.
 */
enum ShootingShotResult: string
{
    case MISSED = 'missed';
    case TOUCHED = 'touched';
    case SUCCESSFUL = 'successful';
    case CARREAU = 'carreau';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $r) => $r->value, self::cases());
    }
}
