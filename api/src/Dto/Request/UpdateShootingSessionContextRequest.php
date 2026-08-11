<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateShootingSessionContextRequest
{
    #[Assert\Length(max: 100)]
    public ?string $title = null;

    #[Assert\Length(max: 2000)]
    public ?string $description = null;
}
