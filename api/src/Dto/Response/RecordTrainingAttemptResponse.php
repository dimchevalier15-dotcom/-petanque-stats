<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class RecordTrainingAttemptResponse
{
    public function __construct(
        public int $number,
        public string $result,
        public int $score,
        public int $currentScore,
        public int $attemptsCount,
        public bool $sessionFinished,
        public ?TrainingSessionSummaryResponse $summary,
    ) {
    }
}
