<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ShootingDistance;
use App\Enum\ShootingShotResult;
use App\Enum\ShootingWorkshop;
use App\Repository\ShootingShotRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * A single precision shot: one workshop, one distance, one result, one score.
 * Exactly 20 rows exist for a completed ShootingSession (5 workshops x 4 distances).
 */
#[ORM\Entity(repositoryClass: ShootingShotRepository::class)]
#[ORM\Table(name: 'shooting_shots')]
#[ORM\UniqueConstraint(name: 'uniq_shooting_shot_session_workshop_distance', columns: ['session_id', 'workshop', 'distance'])]
class ShootingShot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ShootingSession::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ShootingSession $session;

    #[ORM\Column(type: 'smallint', enumType: ShootingWorkshop::class)]
    private ShootingWorkshop $workshop;

    #[ORM\Column(type: 'smallint', enumType: ShootingDistance::class)]
    private ShootingDistance $distance;

    #[ORM\Column(type: 'string', length: 10, enumType: ShootingShotResult::class)]
    private ShootingShotResult $result;

    #[ORM\Column(type: 'smallint')]
    private int $score;

    public function __construct(
        ShootingSession $session,
        ShootingWorkshop $workshop,
        ShootingDistance $distance,
        ShootingShotResult $result,
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

    public function getSession(): ShootingSession
    {
        return $this->session;
    }

    public function getWorkshop(): ShootingWorkshop
    {
        return $this->workshop;
    }

    public function getDistance(): ShootingDistance
    {
        return $this->distance;
    }

    public function getResult(): ShootingShotResult
    {
        return $this->result;
    }

    public function getScore(): int
    {
        return $this->score;
    }
}
