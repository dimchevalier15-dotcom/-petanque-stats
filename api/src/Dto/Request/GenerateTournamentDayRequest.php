<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class GenerateTournamentDayRequest
{
    /**
     * @var list<int>
     */
    #[Assert\All([new Assert\Positive()])]
    public array $absentTeamIds = [];

    /**
     * @var list<string>
     */
    #[Assert\Count(max: 12)]
    #[Assert\All([new Assert\NotBlank(), new Assert\Length(max: 80)])]
    public array $fakeTeamNames = [];

    /**
     * @var list<int>
     */
    #[Assert\All([new Assert\Positive()])]
    public array $excludedLaneNumbers = [];
}
