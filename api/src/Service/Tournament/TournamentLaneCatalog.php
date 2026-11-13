<?php

declare(strict_types=1);

namespace App\Service\Tournament;

final class TournamentLaneCatalog
{
    /**
     * Usable lane numbers: walk upward from 1 and skip excluded numbers
     * until {@see $numberOfLanes} lanes have been collected.
     *
     * @param list<int> $excludedLaneNumbers
     * @return list<int>
     */
    public function usableLaneNumbers(int $numberOfLanes, array $excludedLaneNumbers): array
    {
        if ($numberOfLanes < 1) {
            throw new TournamentRuleException('invalid_settings', 'At least one lane is required.', 422);
        }

        $excluded = [];
        foreach ($excludedLaneNumbers as $laneNumber) {
            $excluded[(int) $laneNumber] = true;
        }

        $usable = [];
        $candidate = 1;
        $guard = $numberOfLanes + count($excluded) + 1;
        while (count($usable) < $numberOfLanes) {
            if (!isset($excluded[$candidate])) {
                $usable[] = $candidate;
            }
            ++$candidate;
            if ($candidate > $guard + 1000) {
                throw new TournamentRuleException('lane_unavailable', 'Lane numbers could not be resolved.', 422);
            }
        }

        return $usable;
    }

    /**
     * @param list<int> $laneNumbers
     * @return list<int>
     */
    public function normalizeExcluded(array $laneNumbers): array
    {
        $unique = [];
        foreach ($laneNumbers as $laneNumber) {
            $normalized = (int) $laneNumber;
            if ($normalized < 1) {
                throw new TournamentRuleException('lane_unavailable', 'Lane numbers must be positive.', 422);
            }
            $unique[$normalized] = $normalized;
        }
        $list = array_values($unique);
        sort($list);

        return $list;
    }
}
