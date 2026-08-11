<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ShootingSessionRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use LogicException;

/**
 * A full "tir de précision" practice session for a single Player.
 *
 * A session is composed of exactly 20 ShootingShot (5 workshops x 4 distances),
 * recorded independently of any Game/match statistics.
 */
#[ORM\Entity(repositoryClass: ShootingSessionRepository::class)]
#[ORM\Table(name: 'shooting_sessions')]
class ShootingSession
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

    #[ORM\Column(name: 'finished_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $finishedAt = null;

    /**
     * Cached total score, recomputed and stored only when the session is
     * completed. The shots themselves remain the source of truth.
     */
    #[ORM\Column(name: 'total_score', type: 'smallint', nullable: true)]
    private ?int $totalScore = null;

    /**
     * Optional free-form context added by the player, typically once the
     * session is finished (e.g. "Entraînement club", "Avant compétition").
     */
    #[ORM\Column(name: 'title', length: 100, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(name: 'description', length: 2000, nullable: true)]
    private ?string $description = null;

    public function __construct(Player $player)
    {
        $this->player = $player;
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
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
            throw new LogicException('This shooting session is already finished.');
        }

        $this->finishedAt = new DateTimeImmutable();
        $this->totalScore = $totalScore;
    }

    public function setContext(?string $title, ?string $description): void
    {
        $this->title = $title;
        $this->description = $description;
    }
}
