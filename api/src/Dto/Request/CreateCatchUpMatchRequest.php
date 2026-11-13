<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateCatchUpMatchRequest
{
    #[Assert\Positive]
    public int $team1Id;

    #[Assert\Positive]
    public int $team2Id;

    #[Assert\Positive]
    public int $laneNumber;
}
