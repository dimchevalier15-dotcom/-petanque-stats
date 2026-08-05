<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class CreatePlayerRequest
{
    #[Assert\NotBlank(message: 'This field is required.')]
    public string $firstName;

    #[Assert\NotBlank(message: 'This field is required.')]
    public string $lastName;

    public ?string $nickname = null;
}
