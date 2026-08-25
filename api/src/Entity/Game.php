<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\GameType;
use App\Enum\MatchNature;
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

    #[ORM\Column(type: 'string', length: 20, enumType: GameType::class)]
    private GameType $type;

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

    #[ORM\Column(type: 'string', length: 20, nullable: true, enumType: MatchNature::class)]
    private ?MatchNature $nature = null;

    #[ORM\ManyToOne(targetEntity: Competition::class)]
    #[ORM\JoinColumn(name: 'competition_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Competition $competition = null;

    #[ORM\Column(name: 'competition_name', type: 'string', length: 255, nullable: true)]
    private ?string $competitionName = null;

    #[ORM\Column(name: 'competition_stage', type: 'string', length: 20, nullable: true)]
    private ?string $competitionStage = null;

    #[ORM\Column(name: 'terrain_type', type: 'string', length: 50, nullable: true)]
    private ?string $terrainType = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    public function __construct(GameType $type, int $targetScore = 13, string $statisticsMode = 'standard')
    {
        $this->type = $type;
        $this->targetScore = $targetScore;
        $this->statisticsMode = $statisticsMode;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getType(): GameType { return $this->type; }
    public function getTargetScore(): int { return $this->targetScore; }
    public function getStatisticsMode(): string { return $this->statisticsMode; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getComment(): ?string { return $this->comment; }
    public function getTeamAName(): ?string { return $this->teamAName; }
    public function getTeamBName(): ?string { return $this->teamBName; }
    public function getNature(): ?MatchNature { return $this->nature; }
    public function getCompetition(): ?Competition { return $this->competition; }
    public function getCompetitionName(): ?string { return $this->competitionName; }
    public function getCompetitionStage(): ?string { return $this->competitionStage; }
    public function getTerrainType(): ?string { return $this->terrainType; }

    public function setComment(?string $comment): void { $this->comment = $comment; }
    public function setTeamAName(?string $teamAName): void { $this->teamAName = $teamAName; }
    public function setTeamBName(?string $teamBName): void { $this->teamBName = $teamBName; }
    public function setNature(?MatchNature $nature): void { $this->nature = $nature; }
    public function setCompetition(?Competition $competition): void { $this->competition = $competition; }
    public function setCompetitionName(?string $competitionName): void { $this->competitionName = $competitionName; }
    public function setCompetitionStage(?string $competitionStage): void { $this->competitionStage = $competitionStage; }
    public function setTerrainType(?string $terrainType): void { $this->terrainType = $terrainType; }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): void
    {
        $this->createdBy = $createdBy;
    }
}
