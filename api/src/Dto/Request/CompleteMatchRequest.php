<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enum\GameType;
use Symfony\Component\Validator\Constraints as Assert;

final class CompleteMatchRequest
{
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [GameType::class, 'values'])]
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

    /** @var list<CompleteMatchSubstitutionDto> */
    public array $substitutions = [];

    /** @var list<CompleteMatchEndDto> */
    #[Assert\NotBlank]
    public array $ends = [];

    #[Assert\GreaterThanOrEqual(0)]
    public int $openingScoreA = 0;

    #[Assert\GreaterThanOrEqual(0)]
    public int $openingScoreB = 0;
}

final class CompleteMatchSubstitutionDto
{
    #[Assert\Choice(choices: ['A', 'B'])]
    public string $team;

    #[Assert\Positive]
    public int $outPlayerId;

    #[Assert\Positive]
    public int $inPlayerId;

    #[Assert\Positive]
    public int $fromEndIndex;
}

final class CompleteMatchEndDto
{
    #[Assert\Positive]
    public int $index;

    #[Assert\Choice(choices: ['A','B'])]
    public string $winner;

    /**
     * When canceled=true, points must be 0.
     * A non-canceled 0-point end is valid (jack out on the last ball).
     * Canceled ends still carry played balls for statistics.
     */
    #[Assert\GreaterThanOrEqual(value: 0)]
    public int $points;

    public bool $canceled = false;

    /** @var list<CompleteMatchEndShotDto> */
    public array $shots = [];

    /**
     * @deprecated Use shots with global sequenceOrder instead.
     * @var list<CompleteMatchEndBallDto>
     */
    public array $balls = [];

    /**
     * Role of each player during this end (for statistics).
     * @var list<CompleteMatchEndRoleDto>
     */
    public array $roles = [];
}

final class CompleteMatchEndRoleDto
{
    #[Assert\Positive]
    public int $playerId;

    #[Assert\Choice(callback: [\App\Enum\PlayerRole::class, 'values'])]
    public string $role;
}

final class CompleteMatchEndShotDto
{
    #[Assert\Positive]
    public int $sequenceOrder;

    #[Assert\Positive]
    public int $playerId;

    /** @var int */
    public int $note;

    #[Assert\Choice(choices: ['point', 'tir'])]
    public string $shotType = 'point';

    public ?float $distance = null;

    public bool $isCochonnet = false;
}

final class CompleteMatchEndBallDto
{
    #[Assert\Positive]
    public int $playerId;

    /** @var list<int> */
    public array $notes = [];

    /** @var list<string> shot types aligned with notes: values 'point' | 'tir' */
    public array $shotTypes = [];

    /**
     * Optional distance in meters for each ball, aligned with notes by index.
     * A ball can be recorded without a distance; the value is then null.
     *
     * @var list<float|null>
     */
    public array $distances = [];

    /**
     * Cochonnet shots are tracked separately and excluded from tir statistics.
     *
     * @var list<bool>
     */
    public array $isCochonnet = [];
}
