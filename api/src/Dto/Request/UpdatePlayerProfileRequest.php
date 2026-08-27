<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdatePlayerProfileRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $firstName;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $lastName;

    #[Assert\Length(max: 100)]
    public ?string $nickname = null;

    #[Assert\Positive]
    public ?int $clubId = null;
}
