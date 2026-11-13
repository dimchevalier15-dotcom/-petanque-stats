<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TournamentDayTeamSlotRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentDayTeamSlotRepository::class)]
#[ORM\Table(name: 'tournament_day_team_slots')]
#[ORM\UniqueConstraint(name: 'uniq_tournament_day_team', columns: ['day_id', 'team_id'])]
class TournamentDayTeamSlot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TournamentDay::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private TournamentDay $day;

    #[ORM\ManyToOne(targetEntity: TournamentTeam::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private TournamentTeam $team;

    #[ORM\ManyToOne(targetEntity: TournamentMatch::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private TournamentMatch $match;

    public function __construct(TournamentDay $day, TournamentTeam $team, TournamentMatch $match)
    {
        $this->day = $day;
        $this->team = $team;
        $this->match = $match;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDay(): TournamentDay
    {
        return $this->day;
    }

    public function getTeam(): TournamentTeam
    {
        return $this->team;
    }

    public function getMatch(): TournamentMatch
    {
        return $this->match;
    }
}
