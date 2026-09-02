<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class LiveMatchResponse
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public string $uuid,
        public string $status,
        public array $data,
        public string $createdAt,
        public string $updatedAt,
        public ?string $finishedAt = null,
        public int $timerAccumulatedMs = 0,
        public bool $timerRunning = false,
        public ?string $timerRunningSince = null,
    ) {
    }
}
