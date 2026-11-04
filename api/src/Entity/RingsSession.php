<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\RingsMode;
use App\Repository\RingsSessionRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use LogicException;

/**
 * A "point dans les ronds" practice session for a single Player.
 * Exactly 15 RingsThrow once finished, independent of match stats.
 */
#[ORM\Entity(repositoryClass: RingsSessionRepository::class)]
#[ORM\Table(name: 'rings_sessions')]
class RingsSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private Player $player;

    #[ORM\Column(name: 'mode', type: 'string', length: 10, enumType: RingsMode::class)]
    private RingsMode $mode;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'played_at', type: 'datetime_immutable')]
    private DateTimeImmutable $playedAt;

    #[ORM\Column(name: 'finished_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $finishedAt = null;

    #[ORM\Column(name: 'total_score', type: 'smallint', nullable: true)]
    private ?int $totalScore = null;

    public function __construct(Player $player, RingsMode $mode = RingsMode::POINT)
    {
        $this->player = $player;
        $this->mode = $mode;
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

    public function getMode(): RingsMode
    {
        return $this->mode;
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
            throw new LogicException('This rings session is already finished.');
        }

        $this->finishedAt = new DateTimeImmutable();
        $this->playedAt = $this->finishedAt;
        $this->totalScore = $totalScore;
    }
}
