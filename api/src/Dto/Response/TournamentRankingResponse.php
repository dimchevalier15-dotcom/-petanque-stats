<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class TournamentRankingResponse
{
    /**
     * @param list<TournamentStandingResponse> $rows
     */
    public function __construct(
        public array $rows,
        public bool $tieUnresolved,
    ) {
    }
}
