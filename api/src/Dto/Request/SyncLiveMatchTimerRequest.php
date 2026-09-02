<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class SyncLiveMatchTimerRequest
{
    #[Assert\NotNull]
    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    public int $accumulatedMs = 0;

    #[Assert\Type('string')]
    public ?string $runningSince = null;
}
