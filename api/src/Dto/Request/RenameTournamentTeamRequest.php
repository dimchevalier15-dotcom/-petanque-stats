<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class RenameTournamentTeamRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    public string $name = '';
}
