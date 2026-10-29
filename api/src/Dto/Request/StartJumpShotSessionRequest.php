<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class StartJumpShotSessionRequest
{
    #[Assert\Choice(choices: ['standard', 'simplified'])]
    public ?string $progressionMode = null;
}
