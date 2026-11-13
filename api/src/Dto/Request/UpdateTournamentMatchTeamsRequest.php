<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateTournamentMatchTeamsRequest
{
    #[Assert\NotNull]
    #[Assert\Positive]
    public int $team1Id = 0;

    #[Assert\NotNull]
    #[Assert\Positive]
    public int $team2Id = 0;
}
