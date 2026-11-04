<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enum\JumpShotProgressionMode;
use Symfony\Component\Validator\Constraints as Assert;

final class StartJumpShotSessionRequest
{
    #[Assert\Choice(callback: [JumpShotProgressionMode::class, 'values'])]
    public ?string $progressionMode = null;
}
