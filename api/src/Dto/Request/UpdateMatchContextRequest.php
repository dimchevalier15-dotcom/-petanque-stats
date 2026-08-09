<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateMatchContextRequest
{
    #[Assert\Length(max: 2000)]
    public ?string $comment = null;

    #[Assert\Length(max: 100)]
    public ?string $teamAName = null;

    #[Assert\Length(max: 100)]
    public ?string $teamBName = null;

    #[Assert\Choice(choices: ['friendly', 'training', 'competition', 'official'])]
    public ?string $nature = null;

    #[Assert\Length(max: 255)]
    public ?string $competitionName = null;

    #[Assert\Choice(choices: [
        'group',
        'swiss',
        'top_64',
        'top_32',
        'top_16',
        'quarter_final',
        'semi_final',
        'final',
        'other',
    ])]
    public ?string $competitionStage = null;

    #[Assert\Length(max: 50)]
    public ?string $terrainType = null;
}
