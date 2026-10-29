<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class SubmitJumpShotSeriesResponse
{
    public function __construct(
        public JumpShotSeriesSummaryResponse $series,
        public int $nextDistance,
        public bool $sessionWon,
        public bool $sessionFinished,
        public JumpShotSessionSummaryResponse $session,
    ) {
    }
}
