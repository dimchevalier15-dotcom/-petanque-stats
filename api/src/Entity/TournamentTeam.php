<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TournamentTeamRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentTeamRepository::class)]
#[ORM\Table(name: 'tournament_teams')]
class TournamentTeam
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tournament::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Tournament $tournament;

    #[ORM\Column(type: 'string', length: 120)]
    private string $name;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, TournamentTeamPlayer>
     */
    #[ORM\OneToMany(targetEntity: TournamentTeamPlayer::class, mappedBy: 'team')]
    private Collection $players;

    public function __construct(Tournament $tournament, string $name)
    {
        $this->tournament = $tournament;
        $this->name = $name;
        $this->createdAt = new DateTimeImmutable();
        $this->players = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTournament(): Tournament
    {
        return $this->tournament;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function rename(string $name): void
    {
        $this->name = $name;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, TournamentTeamPlayer>
     */
    public function getPlayers(): Collection
    {
        return $this->players;
    }
}
