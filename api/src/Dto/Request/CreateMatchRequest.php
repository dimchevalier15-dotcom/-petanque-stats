<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateMatchRequest
{
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['tete_a_tete','doublette','triplette'])]
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

    /**
     * @var list<int> Player ids that will have their individual statistics tracked
     * Optional; when empty, defaults to all selected players server-side.
     */
    public array $trackedPlayers = [];
}
