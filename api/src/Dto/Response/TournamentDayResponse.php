<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class TournamentDayResponse
{
    /**
     * @param list<int> $excludedLaneNumbers
     * @param list<int> $usableLaneNumbers
     * @param list<TournamentMatchResponse> $matches
     */
    public function __construct(
        public int $id,
        public int $number,
        public string $phase,
        public string $status,
        public ?string $playoffRound,
        public ?string $closedAt,
        public array $excludedLaneNumbers,
        public array $usableLaneNumbers,
        public bool $readyToClose,
        public array $matches,
    ) {
    }
}
