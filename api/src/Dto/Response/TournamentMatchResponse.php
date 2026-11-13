<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class TournamentMatchResponse
{
    public function __construct(
        public int $id,
        public TournamentMatchSideResponse $team1,
        public TournamentMatchSideResponse $team2,
        public ?int $score1,
        public ?int $score2,
        public int $laneNumber,
        public string $status,
        public bool $fictional,
        public string $phase,
        public ?string $playoffRound,
        public ?int $playoffSlot,
        public bool $highlighted,
        public bool $viewerCanEditScore,
    ) {
    }
}
