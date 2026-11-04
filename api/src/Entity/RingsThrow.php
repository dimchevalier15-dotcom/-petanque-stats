<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\RingsTarget;
use App\Repository\RingsThrowRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RingsThrowRepository::class)]
#[ORM\Table(name: 'rings_throws')]
#[ORM\UniqueConstraint(name: 'uniq_rings_throw_session_position', columns: ['session_id', 'position'])]
class RingsThrow
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: RingsSession::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private RingsSession $session;

    #[ORM\Column(type: 'smallint')]
    private int $position;

    #[ORM\Column(type: 'string', length: 10, enumType: RingsTarget::class)]
    private RingsTarget $target;

    #[ORM\Column(type: 'boolean')]
    private bool $success;

    #[ORM\Column(type: 'smallint')]
    private int $score;

    public function __construct(RingsSession $session, int $position, RingsTarget $target, bool $success)
    {
        $this->session = $session;
        $this->position = $position;
        $this->target = $target;
        $this->success = $success;
        $this->score = $success ? $target->points() : 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSession(): RingsSession
    {
        return $this->session;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getTarget(): RingsTarget
    {
        return $this->target;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getScore(): int
    {
        return $this->score;
    }
}
