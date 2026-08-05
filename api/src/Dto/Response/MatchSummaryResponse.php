<?php

declare(strict_types=1);

namespace App\Dto\Response;

/**
 * Match summary DTO returned after a game is completed.
 */
final class MatchSummaryResponse
{
    /** @param list<MatchSummaryPlayerRow> $players */
    public function __construct(
        public int $matchId,
        public int $scoreA,
        public int $scoreB,
        public string $winner, // 'A' | 'B'
        public int $ends,
        public array $players,
    ) {
    }
}
