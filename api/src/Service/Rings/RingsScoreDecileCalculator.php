<?php

declare(strict_types=1);

namespace App\Service\Rings;

/**
 * Buckets session total scores into 10 equal bands from 0 to maxScore (75).
 */
final class RingsScoreDecileCalculator
{
    public const DECILE_COUNT = 10;

    public function __construct(private int $maxScore = 75)
    {
    }

    /**
     * @return list<array{decile:int,minScore:int,maxScore:int,sessionCount:int}>
     */
    public function buildDistribution(array $sessionScores): array
    {
        $counts = array_fill(1, self::DECILE_COUNT, 0);
        foreach ($sessionScores as $score) {
            $decile = $this->decileForScore((int) $score);
            ++$counts[$decile];
        }

        $out = [];
        for ($decile = 1; $decile <= self::DECILE_COUNT; ++$decile) {
            [$min, $max] = $this->boundsForDecile($decile);
            $out[] = [
                'decile' => $decile,
                'minScore' => $min,
                'maxScore' => $max,
                'sessionCount' => $counts[$decile],
            ];
        }

        return $out;
    }

    public function decileForScore(int $score): int
    {
        $score = max(0, min($this->maxScore, $score));
        if ($score >= $this->maxScore) {
            return self::DECILE_COUNT;
        }

        $band = (int) ($this->maxScore / self::DECILE_COUNT);

        return min(self::DECILE_COUNT, (int) floor($score / $band) + 1);
    }

    /**
     * @return array{0:int,1:int}
     */
    public function boundsForDecile(int $decile): array
    {
        $decile = max(1, min(self::DECILE_COUNT, $decile));
        $band = (int) ($this->maxScore / self::DECILE_COUNT);
        $min = ($decile - 1) * $band;
        $max = $decile === self::DECILE_COUNT ? $this->maxScore : $decile * $band - 1;

        return [$min, $max];
    }
}
