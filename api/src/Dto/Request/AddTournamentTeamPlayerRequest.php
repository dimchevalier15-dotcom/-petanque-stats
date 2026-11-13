<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class AddTournamentTeamPlayerRequest
{
    #[Assert\NotNull]
    #[Assert\Positive]
    public int $playerId = 0;
}
