<?php

declare(strict_types=1);

namespace App\Service\Tournament;

final class TournamentPairKey
{
    public static function of(int $teamAId, int $teamBId): string
    {
        $low = min($teamAId, $teamBId);
        $high = max($teamAId, $teamBId);

        return $low.'-'.$high;
    }
}
