<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'matches')]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Allowed values: tete_a_tete, doublette, triplette
    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(name: 'target_score', type: 'integer')]
    private int $targetScore = 13;

    // Allowed values: standard, simple
    #[ORM\Column(name: 'statistics_mode', type: 'string', length: 10)]
    private string $statisticsMode = 'standard';

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(string $type, int $targetScore = 13, string $statisticsMode = 'standard')
    {
        $this->type = $type;
        $this->targetScore = $targetScore;
        $this->statisticsMode = $statisticsMode;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getType(): string { return $this->type; }
    public function getTargetScore(): int { return $this->targetScore; }
    public function getStatisticsMode(): string { return $this->statisticsMode; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
}
