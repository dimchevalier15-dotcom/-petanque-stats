<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Response\MatchSummaryShotBreakdown;

final class ShotBreakdownFactory
{
    public function __construct(private MatchSuccessRateCalculator $successRate)
    {
    }

    /**
     * @param array{count:int,sum:int,p2:int,p1:int,p0:int,m1:int,m2:int} $raw
     */
    public function fromAggregate(array $raw): MatchSummaryShotBreakdown
    {
        $p2 = (int) $raw['p2'];
        $p1 = (int) $raw['p1'];
        $p0 = (int) $raw['p0'];
        $m1 = (int) $raw['m1'];
        $m2 = (int) $raw['m2'];
        $avg = $raw['count'] > 0 ? round($raw['sum'] / $raw['count'], 2) : 0.0;

        return new MatchSummaryShotBreakdown(
            average: $avg,
            p2: $p2,
            p1: $p1,
            p0: $p0,
            m1: $m1,
            m2: $m2,
            successRate: $this->successRate->fromNoteCounts($p2, $p1, $p0, $m1, $m2),
        );
    }
}
