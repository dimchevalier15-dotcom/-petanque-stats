<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ShootingDistance;
use App\Repository\JumpShotSessionRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use LogicException;

#[ORM\Entity(repositoryClass: JumpShotSessionRepository::class)]
#[ORM\Table(name: 'jump_shot_sessions')]
class JumpShotSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private Player $player;

    #[ORM\Column(name: 'current_distance', type: 'smallint', enumType: ShootingDistance::class)]
    private ShootingDistance $currentDistance;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'finished_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $finishedAt = null;

    #[ORM\Column(name: 'won', type: 'boolean', options: ['default' => false])]
    private bool $won = false;

    public function __construct(Player $player)
    {
        $this->player = $player;
        $this->currentDistance = ShootingDistance::SIX_METERS;
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

    public function getCurrentDistance(): ShootingDistance
    {
        return $this->currentDistance;
    }

    public function setCurrentDistance(ShootingDistance $distance): void
    {
        $this->currentDistance = $distance;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getFinishedAt(): ?DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function isWon(): bool
    {
        return $this->won;
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

    public function markWon(): void
    {
        if ($this->isFinished()) {
            throw new LogicException('This jump shot session is already finished.');
        }

        $this->finishedAt = new DateTimeImmutable();
        $this->won = true;
    }

    public function reopenAfterSeriesRevert(ShootingDistance $distance): void
    {
        $this->currentDistance = $distance;
        $this->finishedAt = null;
        $this->won = false;
    }
}
