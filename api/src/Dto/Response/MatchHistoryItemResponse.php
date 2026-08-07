<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class MatchHistoryItemResponse
{
    public function __construct(
        public int $id,
        public string $date, // ISO datetime string
        public string $type, // tete_a_tete | doublette | triplette
        public int $scoreA,
        public int $scoreB,
        public string $winner, // 'A' | 'B'
        public bool $victory, // from current player's perspective
    ) {
    }
}
