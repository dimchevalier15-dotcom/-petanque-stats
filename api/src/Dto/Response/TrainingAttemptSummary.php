<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class TrainingAttemptSummary
{
    public function __construct(
        public int $number,
        public string $result,
        public int $score,
    ) {
    }
}
