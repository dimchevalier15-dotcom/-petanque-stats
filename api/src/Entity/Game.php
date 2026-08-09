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

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(name: 'team_a_name', type: 'string', length: 100, nullable: true)]
    private ?string $teamAName = null;

    #[ORM\Column(name: 'team_b_name', type: 'string', length: 100, nullable: true)]
    private ?string $teamBName = null;

    // friendly | training | competition | official
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $nature = null;

    #[ORM\Column(name: 'competition_name', type: 'string', length: 255, nullable: true)]
    private ?string $competitionName = null;

    #[ORM\Column(name: 'competition_stage', type: 'string', length: 20, nullable: true)]
    private ?string $competitionStage = null;

    #[ORM\Column(name: 'terrain_type', type: 'string', length: 50, nullable: true)]
    private ?string $terrainType = null;

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
    public function getComment(): ?string { return $this->comment; }
    public function getTeamAName(): ?string { return $this->teamAName; }
    public function getTeamBName(): ?string { return $this->teamBName; }
    public function getNature(): ?string { return $this->nature; }
    public function getCompetitionName(): ?string { return $this->competitionName; }
    public function getCompetitionStage(): ?string { return $this->competitionStage; }
    public function getTerrainType(): ?string { return $this->terrainType; }

    public function setComment(?string $comment): void { $this->comment = $comment; }
    public function setTeamAName(?string $teamAName): void { $this->teamAName = $teamAName; }
    public function setTeamBName(?string $teamBName): void { $this->teamBName = $teamBName; }
    public function setNature(?string $nature): void { $this->nature = $nature; }
    public function setCompetitionName(?string $competitionName): void { $this->competitionName = $competitionName; }
    public function setCompetitionStage(?string $competitionStage): void { $this->competitionStage = $competitionStage; }
    public function setTerrainType(?string $terrainType): void { $this->terrainType = $terrainType; }
}
