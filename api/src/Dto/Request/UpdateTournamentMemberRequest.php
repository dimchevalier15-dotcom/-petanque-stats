<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateTournamentMemberRequest
{
    #[Assert\NotNull]
    public bool $admin = false;
}
