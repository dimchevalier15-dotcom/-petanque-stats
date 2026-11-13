<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class TournamentDetailResponse
{
    /**
     * @param list<int> $excludedLaneNumbers
     * @param list<TournamentTeamResponse> $teams
     * @param list<TournamentMemberResponse>|null $members
     * @param list<TournamentDayResponse> $days
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public string $status,
        public string $format,
        public ?string $startsAt,
        public ?string $endsAt,
        public int $minPlayersPerTeam,
        public int $maxPlayersPerTeam,
        public int $pointsForWin,
        public int $pointsForDraw,
        public bool $drawsAllowed,
        public int $numberOfLanes,
        public array $excludedLaneNumbers,
        public bool $viewerIsAdmin,
        public ?int $viewerTeamId,
        public bool $playoffsGenerated,
        public array $teams,
        public ?array $members,
        public array $days,
        public TournamentRankingResponse $ranking,
    ) {
    }
}
