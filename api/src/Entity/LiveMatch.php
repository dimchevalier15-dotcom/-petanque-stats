<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LiveMatchRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LiveMatchRepository::class)]
#[ORM\Table(name: 'live_matches')]
class LiveMatch
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_FINISHED = 'finished';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $uuid;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $data;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(name: 'finished_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $finishedAt = null;

    #[ORM\Column(name: 'timer_started_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $timerStartedAt = null;

    #[ORM\Column(name: 'timer_accumulated_ms', type: 'integer')]
    private int $timerAccumulatedMs = 0;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(string $uuid, array $data)
    {
        $this->uuid = $uuid;
        $this->status = self::STATUS_ACTIVE;
        $this->data = $data;
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function getTimerStartedAt(): ?\DateTimeImmutable
    {
        return $this->timerStartedAt;
    }

    public function getTimerAccumulatedMs(): int
    {
        return $this->timerAccumulatedMs;
    }

    public function isTimerRunning(): bool
    {
        return $this->timerStartedAt !== null;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function replaceData(array $data): void
    {
        if (!$this->isActive()) {
            throw new \DomainException('Cannot update a finished live match.');
        }

        $this->data = $data;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function syncTimer(int $accumulatedMs, ?\DateTimeImmutable $runningSince): void
    {
        if (!$this->isActive()) {
            throw new \DomainException('Cannot sync timer on a finished live match.');
        }

        $this->timerAccumulatedMs = max(0, $accumulatedMs);
        $this->timerStartedAt = $runningSince;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function finish(): void
    {
        if (!$this->isActive()) {
            throw new \DomainException('Live match is not active.');
        }

        $this->status = self::STATUS_FINISHED;
        $now = new \DateTimeImmutable();
        $this->finishedAt = $now;
        $this->updatedAt = $now;
    }
}
