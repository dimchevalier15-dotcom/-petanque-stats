<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TournamentDayFakeTeamRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentDayFakeTeamRepository::class)]
#[ORM\Table(name: 'tournament_day_fake_teams')]
class TournamentDayFakeTeam
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TournamentDay::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private TournamentDay $day;

    #[ORM\Column(type: 'string', length: 80)]
    private string $name;

    public function __construct(TournamentDay $day, string $name)
    {
        $this->day = $day;
        $this->name = $name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDay(): TournamentDay
    {
        return $this->day;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
