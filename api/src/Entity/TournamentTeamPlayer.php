<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TournamentTeamPlayerRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentTeamPlayerRepository::class)]
#[ORM\Table(name: 'tournament_team_players')]
#[ORM\UniqueConstraint(name: 'uniq_tournament_team_player', columns: ['team_id', 'player_id'])]
#[ORM\UniqueConstraint(name: 'uniq_tournament_player', columns: ['tournament_id', 'player_id'])]
class TournamentTeamPlayer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TournamentTeam::class, inversedBy: 'players')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private TournamentTeam $team;

    #[ORM\ManyToOne(targetEntity: Tournament::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Tournament $tournament;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(TournamentTeam $team, Player $player)
    {
        $this->team = $team;
        $this->tournament = $team->getTournament();
        $this->player = $player;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTeam(): TournamentTeam
    {
        return $this->team;
    }

    public function getTournament(): Tournament
    {
        return $this->tournament;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
