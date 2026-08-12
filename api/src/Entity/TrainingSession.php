<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TrainingType;
use App\Repository\TrainingSessionRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use LogicException;

/**
 * A point or tir training session for a single Player, independent from
 * match statistics and precision-shooting sessions.
 */
#[ORM\Entity(repositoryClass: TrainingSessionRepository::class)]
#[ORM\Table(name: 'training_sessions')]
class TrainingSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private Player $player;

    #[ORM\Column(type: 'string', length: 6, enumType: TrainingType::class)]
    private TrainingType $type;

    #[ORM\Column(type: 'float')]
    private float $distance;

    #[ORM\Column(name: 'planned_balls', type: 'smallint')]
    private int $plannedBalls;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'finished_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $finishedAt = null;

    /**
     * Cached total score, stored when the session is finished.
     * Individual attempts remain the source of truth.
     */
    #[ORM\Column(name: 'total_score', type: 'smallint', nullable: true)]
    private ?int $totalScore = null;

    public function __construct(Player $player, TrainingType $type, float $distance, int $plannedBalls)
    {
        $this->player = $player;
        $this->type = $type;
        $this->distance = $distance;
        $this->plannedBalls = $plannedBalls;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getType(): TrainingType
    {
        return $this->type;
    }

    public function getDistance(): float
    {
        return $this->distance;
    }

    public function getPlannedBalls(): int
    {
        return $this->plannedBalls;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getFinishedAt(): ?DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function getTotalScore(): ?int
    {
        return $this->totalScore;
    }

    public function isFinished(): bool
    {
        return $this->finishedAt !== null;
    }

    public function belongsTo(Player $player): bool
    {
        if ($this->player->getId() === null || $player->getId() === null) {
            return $this->player === $player;
        }

        return $this->player->getId() === $player->getId();
    }

    public function markFinished(int $totalScore): void
    {
        if ($this->isFinished()) {
            throw new LogicException('This training session is already finished.');
        }

        $this->finishedAt = new DateTimeImmutable();
        $this->totalScore = $totalScore;
    }
}
