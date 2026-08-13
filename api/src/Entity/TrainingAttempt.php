<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TrainingType;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * A single ball attempt within a training session.
 * Type and distance are denormalized for future statistics queries.
 */
#[ORM\Entity]
#[ORM\Table(name: 'training_attempts')]
class TrainingAttempt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TrainingSession::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private TrainingSession $session;

    /** 1-based ball number within the session. */
    #[ORM\Column(type: 'smallint')]
    private int $number;

    #[ORM\Column(type: 'string', length: 6, enumType: TrainingType::class)]
    private TrainingType $type;

    #[ORM\Column(type: 'float')]
    private float $distance;

    #[ORM\Column(type: 'string', length: 12)]
    private string $result;

    #[ORM\Column(type: 'smallint')]
    private int $score;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(
        TrainingSession $session,
        int $number,
        TrainingType $type,
        float $distance,
        string $result,
        int $score,
    ) {
        $this->session = $session;
        $this->number = $number;
        $this->type = $type;
        $this->distance = $distance;
        $this->result = $result;
        $this->score = $score;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSession(): TrainingSession
    {
        return $this->session;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getType(): TrainingType
    {
        return $this->type;
    }

    public function getDistance(): float
    {
        return $this->distance;
    }

    public function getResult(): string
    {
        return $this->result;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
