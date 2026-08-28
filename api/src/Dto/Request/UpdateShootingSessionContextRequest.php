<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enum\ShootingContextNature;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateShootingSessionContextRequest
{
    #[Assert\Choice(callback: [ShootingContextNature::class, 'values'])]
    public ?string $contextNature = null;

    #[Assert\Length(max: 100)]
    public ?string $title = null;

    #[Assert\Length(max: 2000)]
    public ?string $description = null;

    #[Assert\Regex(pattern: '/^\d{4}-\d{2}-\d{2}$/')]
    public ?string $playedAt = null;
}
