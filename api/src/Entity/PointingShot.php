<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\PointingShotResult;
use App\Enum\PointingWorkshop;
use App\Enum\ShootingDistance;
use App\Repository\PointingShotRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PointingShotRepository::class)]
#[ORM\Table(name: 'pointing_shots')]
#[ORM\UniqueConstraint(name: 'uniq_pointing_shot_session_workshop_distance', columns: ['session_id', 'workshop', 'distance'])]
class PointingShot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PointingSession::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private PointingSession $session;

    #[ORM\Column(type: 'smallint', enumType: PointingWorkshop::class)]
    private PointingWorkshop $workshop;

    #[ORM\Column(type: 'smallint', enumType: ShootingDistance::class)]
    private ShootingDistance $distance;

    #[ORM\Column(type: 'string', length: 10, enumType: PointingShotResult::class)]
    private PointingShotResult $result;

    #[ORM\Column(type: 'smallint')]
    private int $score;

    public function __construct(
        PointingSession $session,
        PointingWorkshop $workshop,
        ShootingDistance $distance,
        PointingShotResult $result,
        int $score,
    ) {
        $this->session = $session;
        $this->workshop = $workshop;
        $this->distance = $distance;
        $this->result = $result;
        $this->score = $score;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSession(): PointingSession
    {
        return $this->session;
    }

    public function getWorkshop(): PointingWorkshop
    {
        return $this->workshop;
    }

    public function getDistance(): ShootingDistance
    {
        return $this->distance;
    }

    public function getResult(): PointingShotResult
    {
        return $this->result;
    }

    public function getScore(): int
    {
        return $this->score;
    }
}
