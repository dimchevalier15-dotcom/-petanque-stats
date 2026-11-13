<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateTournamentRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    public string $name = '';

    #[Assert\Length(max: 2000)]
    public ?string $description = null;

    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 10)]
    public int $minPlayersPerTeam = 1;

    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 10)]
    public int $maxPlayersPerTeam = 1;

    #[Assert\NotNull]
    #[Assert\Range(min: 0, max: 20)]
    public int $pointsForWin = 1;

    #[Assert\NotNull]
    #[Assert\Range(min: 0, max: 20)]
    public int $pointsForDraw = 0;

    #[Assert\NotNull]
    public bool $drawsAllowed = false;

    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 40)]
    public int $numberOfLanes = 1;

    /**
     * @var list<int>
     */
    #[Assert\All([new Assert\Positive()])]
    public array $excludedLaneNumbers = [];

    #[Assert\Date]
    public ?string $startsAt = null;

    #[Assert\Date]
    public ?string $endsAt = null;
}
