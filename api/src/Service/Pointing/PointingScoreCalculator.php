<?php

declare(strict_types=1);

namespace App\Service\Pointing;

use App\Enum\PointingShotResult;
use App\Enum\PointingWorkshop;

/**
 * Official "point de précision" barème: four tiers worth 0, 3, 9 and 15 points
 * on every workshop. Maximum session score: 20 x 15 = 300.
 */
final class PointingScoreCalculator
{
    /** @var array<string,int> */
    private const array POINTS = [
        'missed' => 0,
        'p3' => 3,
        'p9' => 9,
        'p15' => 15,
    ];

    public function isResultAllowedForWorkshop(PointingWorkshop $workshop, PointingShotResult $result): bool
    {
        unset($workshop);

        return true;
    }

    public function pointsFor(PointingWorkshop $workshop, PointingShotResult $result): int
    {
        unset($workshop);

        return self::POINTS[$result->value];
    }
}
