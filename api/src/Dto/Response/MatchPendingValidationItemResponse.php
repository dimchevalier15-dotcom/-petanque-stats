<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class MatchPendingValidationItemResponse
{
    public function __construct(
        public int $matchPlayerId,
        public int $matchId,
        public string $date,
        public string $type,
        public int $scoreA,
        public int $scoreB,
        public string $teamALabel,
        public string $teamBLabel,
        public ?string $nature = null,
        public ?string $competitionLabel = null,
        public ?string $competitionStage = null,
    ) {
    }
}
