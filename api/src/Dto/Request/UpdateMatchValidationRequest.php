<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateMatchValidationRequest
{
    #[Assert\NotNull]
    public ?bool $validated = null;
}
