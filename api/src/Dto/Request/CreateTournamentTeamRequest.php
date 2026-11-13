<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateTournamentTeamRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    public string $name = '';

    /**
     * @var list<int>
     */
    #[Assert\All([new Assert\Positive()])]
    public array $playerIds = [];
}
