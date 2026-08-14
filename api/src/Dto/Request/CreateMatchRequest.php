<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enum\GameType;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateMatchRequest
{
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [GameType::class, 'values'])]
    public string $type = 'doublette';

    #[Assert\Positive]
    public int $targetScore = 13;

    /**
     * Statistics entry mode for this match.
     * Allowed values: standard | simple
     */
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['standard','simple'])]
    public string $statisticsMode = 'standard';

    /** @var list<int> */
    #[Assert\Count(min: 1, max: 3)]
    public array $teamA = [];

    /** @var list<int> */
    #[Assert\Count(min: 1, max: 3)]
    public array $teamB = [];

    #[Assert\Length(max: 100)]
    public ?string $teamAName = null;

    #[Assert\Length(max: 100)]
    public ?string $teamBName = null;

    /**
     * @var list<int> Player ids that will have their individual statistics tracked
     * Optional; when empty, defaults to all selected players server-side.
     */
    public array $trackedPlayers = [];

    /**
     * Optional default shot type per player for this match (from role selection).
     * When empty, server computes defaults by type & slot.
     * @var list<CreateMatchDefaultShotType>
     */
    public array $defaultShotTypes = [];

    /**
     * Initial role per player for this match.
     * @var list<CreateMatchStartingRole>
     */
    public array $startingRoles = [];
}

final class CreateMatchStartingRole
{
    #[Assert\Positive]
    public int $playerId;

    #[Assert\Choice(callback: [\App\Enum\PlayerRole::class, 'values'])]
    public string $role = 'pointeur';
}

final class CreateMatchDefaultShotType
{
    #[Assert\Positive]
    public int $playerId;

    #[Assert\Choice(choices: ['point','tir'])]
    public string $defaultShotType = 'point';
}
