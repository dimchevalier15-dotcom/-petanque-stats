<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\JumpShotSeriesConsequence;
use App\Enum\ShootingDistance;
use App\Repository\JumpShotSeriesRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JumpShotSeriesRepository::class)]
#[ORM\Table(name: 'jump_shot_series')]
#[ORM\UniqueConstraint(name: 'uniq_jump_shot_series_session_number', columns: ['session_id', 'series_number'])]
class JumpShotSeries
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: JumpShotSession::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private JumpShotSession $session;

    #[ORM\Column(name: 'series_number', type: 'smallint')]
    private int $seriesNumber;

    #[ORM\Column(type: 'smallint', enumType: ShootingDistance::class)]
    private ShootingDistance $distance;

    #[ORM\Column(name: 'success_count', type: 'smallint')]
    private int $successCount;

    #[ORM\Column(name: 'is_special_series', type: 'boolean')]
    private bool $isSpecialSeries;

    #[ORM\Column(type: 'string', length: 8, enumType: JumpShotSeriesConsequence::class)]
    private JumpShotSeriesConsequence $consequence;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(
        JumpShotSession $session,
        int $seriesNumber,
        ShootingDistance $distance,
        int $successCount,
        bool $isSpecialSeries,
        JumpShotSeriesConsequence $consequence,
    ) {
        $this->session = $session;
        $this->seriesNumber = $seriesNumber;
        $this->distance = $distance;
        $this->successCount = $successCount;
        $this->isSpecialSeries = $isSpecialSeries;
        $this->consequence = $consequence;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSession(): JumpShotSession
    {
        return $this->session;
    }

    public function getSeriesNumber(): int
    {
        return $this->seriesNumber;
    }

    public function getDistance(): ShootingDistance
    {
        return $this->distance;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function isSpecialSeries(): bool
    {
        return $this->isSpecialSeries;
    }

    public function getConsequence(): JumpShotSeriesConsequence
    {
        return $this->consequence;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
