<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class AddTournamentMemberRequest
{
    #[Assert\NotNull]
    #[Assert\Positive]
    public int $userId = 0;

    public bool $admin = false;
}
