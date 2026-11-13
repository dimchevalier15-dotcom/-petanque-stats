<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateTournamentMatchScoreRequest
{
    #[Assert\Range(min: 0, max: 13)]
    public ?int $score1 = null;

    #[Assert\Range(min: 0, max: 13)]
    public ?int $score2 = null;

    public bool $clear = false;
}
