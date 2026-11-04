<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enum\RingsMode;
use Symfony\Component\Validator\Constraints as Assert;

final class StartRingsSessionRequest
{
    #[Assert\Choice(callback: [RingsMode::class, 'values'])]
    public ?string $mode = null;
}
