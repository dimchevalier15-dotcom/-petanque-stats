<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class ResetPasswordRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 32, max: 128)]
    public string $token;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 255)]
    public string $password;
}
