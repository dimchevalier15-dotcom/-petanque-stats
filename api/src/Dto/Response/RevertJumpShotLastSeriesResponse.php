<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class RevertJumpShotLastSeriesResponse
{
    /**
     * @param list<JumpShotThrowSummaryResponse> $restoredThrows
     */
    public function __construct(
        public JumpShotSessionSummaryResponse $session,
        public array $restoredThrows,
    ) {
    }
}
