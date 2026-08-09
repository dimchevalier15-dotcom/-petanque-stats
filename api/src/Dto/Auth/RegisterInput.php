<?php

declare(strict_types=1);

namespace App\Dto\Auth;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 255)]
    public string $password;

    #[Assert\When(
        expression: 'this.playerId === null',
        constraints: [
            new Assert\NotBlank(),
            new Assert\Length(max: 100),
        ],
    )]
    public ?string $firstName = null;

    #[Assert\When(
        expression: 'this.playerId === null',
        constraints: [
            new Assert\NotBlank(),
            new Assert\Length(max: 100),
        ],
    )]
    public ?string $lastName = null;

    #[Assert\Length(max: 100)]
    public ?string $nickname = null;

    #[Assert\Positive]
    public ?int $playerId = null;
}
