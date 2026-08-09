<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class MatchContextResponse
{
    public function __construct(
        public int $matchId,
        public ?string $comment,
        public ?string $teamAName,
        public ?string $teamBName,
        public ?string $nature,
        public ?string $competitionName,
        public ?string $competitionStage,
        public ?string $terrainType,
    ) {
    }
}
