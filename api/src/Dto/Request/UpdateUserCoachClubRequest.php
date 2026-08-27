<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateUserCoachClubRequest
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';

    public ?int $clubId = null;
}
