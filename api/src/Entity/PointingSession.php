<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ShootingContextNature;
use App\Repository\PointingSessionRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use LogicException;

/**
 * A full "point de précision" practice session for a single Player.
 * Exactly 20 PointingShot (5 workshops x 4 distances), independent of match stats.
 */
#[ORM\Entity(repositoryClass: PointingSessionRepository::class)]
#[ORM\Table(name: 'pointing_sessions')]
class PointingSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private Player $player;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'played_at', type: 'datetime_immutable')]
    private DateTimeImmutable $playedAt;

    #[ORM\Column(name: 'finished_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $finishedAt = null;

    #[ORM\Column(name: 'total_score', type: 'smallint', nullable: true)]
    private ?int $totalScore = null;

    #[ORM\Column(name: 'title', length: 100, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(name: 'description', length: 2000, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'context_nature', type: 'string', length: 11, enumType: ShootingContextNature::class, nullable: true)]
    private ?ShootingContextNature $contextNature = null;

    public function __construct(Player $player)
    {
        $this->player = $player;
        $this->createdAt = new DateTimeImmutable();
        $this->playedAt = $this->createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPlayedAt(): DateTimeImmutable
    {
        return $this->playedAt;
    }

    public function getFinishedAt(): ?DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function getTotalScore(): ?int
    {
        return $this->totalScore;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getContextNature(): ?ShootingContextNature
    {
        return $this->contextNature;
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
            throw new LogicException('This pointing session is already finished.');
        }

        $this->finishedAt = new DateTimeImmutable();
        $this->playedAt = $this->finishedAt;
        $this->totalScore = $totalScore;
    }
}
