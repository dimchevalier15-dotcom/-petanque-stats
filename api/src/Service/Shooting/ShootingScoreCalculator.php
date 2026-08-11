<?php

declare(strict_types=1);

namespace App\Service\Shooting;

use App\Enum\ShootingShotResult;
use App\Enum\ShootingWorkshop;
use InvalidArgumentException;

/**
 * Centralizes the official "tir de précision" scoring barème.
 *
 * Workshops 1 to 4 (ball targets) use the standard scale:
 * missed=0, touched=1, successful=3, carreau=5.
 *
 * Workshop 5 (jack) has its own official rules: there is no "carreau"
 * category, and touching/succeeding is worth more points than in the
 * other workshops: missed=0, touched=3, successful=5.
 */
final class ShootingScoreCalculator
{
    /** @var array<string,int> */
    private const array STANDARD_POINTS = [
        'missed' => 0,
        'touched' => 1,
        'successful' => 3,
        'carreau' => 5,
    ];

    /** @var array<string,int> */
    private const array JACK_WORKSHOP_POINTS = [
        'missed' => 0,
        'touched' => 3,
        'successful' => 5,
    ];

    public function isResultAllowedForWorkshop(ShootingWorkshop $workshop, ShootingShotResult $result): bool
    {
        if ($workshop === ShootingWorkshop::JACK) {
            return $result !== ShootingShotResult::CARREAU;
        }

        return true;
    }

    public function pointsFor(ShootingWorkshop $workshop, ShootingShotResult $result): int
    {
        if (!$this->isResultAllowedForWorkshop($workshop, $result)) {
            throw new InvalidArgumentException(sprintf(
                'Result "%s" is not allowed for workshop "%s".',
                $result->value,
                $workshop->name,
            ));
        }

        $table = $workshop === ShootingWorkshop::JACK ? self::JACK_WORKSHOP_POINTS : self::STANDARD_POINTS;

        return $table[$result->value];
    }
}
