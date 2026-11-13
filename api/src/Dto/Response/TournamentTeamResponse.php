<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class TournamentTeamResponse
{
    /**
     * @param list<TournamentTeamPlayerResponse> $players
     */
    public function __construct(
        public int $id,
        public string $name,
        public array $players,
    ) {
    }
}
