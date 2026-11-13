<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class TournamentMatchSideResponse
{
    public function __construct(
        public ?int $teamId,
        public ?int $fakeTeamId,
        public ?string $name,
        public bool $fictional,
        public ?string $pendingWinnerOfRound,
        public ?int $pendingWinnerOfSlot,
    ) {
    }
}
