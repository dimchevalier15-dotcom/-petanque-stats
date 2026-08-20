<?php

declare(strict_types=1);

namespace App\Service;

final class MatchSuccessRateCalculator
{
    public function fromNoteCounts(int $p2, int $p1, int $p0, int $m1, int $m2): ?float
    {
        $total = $p2 + $p1 + $p0 + $m1 + $m2;
        if ($total === 0) {
            return null;
        }

        return round(($p2 + $p1) / $total * 100, 1);
    }
}
