<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UpsertClubRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $name;

    #[Assert\NotNull]
    #[Assert\Positive]
    public int $countryId;

    #[Assert\Length(max: 2000)]
    public ?string $description = null;
}
