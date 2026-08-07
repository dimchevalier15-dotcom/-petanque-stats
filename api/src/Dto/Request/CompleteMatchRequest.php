<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class CompleteMatchRequest
{
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['tete_a_tete','doublette','triplette'])]
    public string $type;

    #[Assert\Positive]
    public int $targetScore;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['standard','simple'])]
    public string $statisticsMode;

    /** @var list<int> */
    #[Assert\Count(min: 1, max: 3)]
    public array $teamA = [];

    /** @var list<int> */
    #[Assert\Count(min: 1, max: 3)]
    public array $teamB = [];

    /** @var list<int> */
    public array $trackedPlayers = [];

    /** @var list<CompleteMatchEndDto> */
    #[Assert\NotBlank]
    public array $ends = [];
}

final class CompleteMatchEndDto
{
    #[Assert\Positive]
    public int $index;

    #[Assert\Choice(choices: ['A','B'])]
    public string $winner;

    /**
     * When canceled=true, points must be 0; otherwise >=1.
     */
    #[Assert\GreaterThanOrEqual(value: 0)]
    public int $points;

    public bool $canceled = false;

    /** @var list<CompleteMatchEndBallDto> */
    public array $balls = [];
}

final class CompleteMatchEndBallDto
{
    #[Assert\Positive]
    public int $playerId;

    /** @var list<int> */
    public array $notes = [];

    /** @var list<string> shot types aligned with notes: values 'point' | 'tir' */
    public array $shotTypes = [];
}
