<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\JumpShotThrowResult;
use App\Repository\JumpShotThrowRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JumpShotThrowRepository::class)]
#[ORM\Table(name: 'jump_shot_throws')]
#[ORM\UniqueConstraint(name: 'uniq_jump_shot_throw_series_position', columns: ['series_id', 'position'])]
class JumpShotThrow
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: JumpShotSeries::class)]
    #[ORM\JoinColumn(name: 'series_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private JumpShotSeries $series;

    #[ORM\Column(type: 'smallint')]
    private int $position;

    #[ORM\Column(type: 'string', length: 10, enumType: JumpShotThrowResult::class)]
    private JumpShotThrowResult $result;

    #[ORM\Column(name: 'recorded_at', type: 'datetime_immutable')]
    private DateTimeImmutable $recordedAt;

    public function __construct(JumpShotSeries $series, int $position, JumpShotThrowResult $result)
    {
        $this->series = $series;
        $this->position = $position;
        $this->result = $result;
        $this->recordedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSeries(): JumpShotSeries
    {
        return $this->series;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getResult(): JumpShotThrowResult
    {
        return $this->result;
    }

    public function getRecordedAt(): DateTimeImmutable
    {
        return $this->recordedAt;
    }
}
