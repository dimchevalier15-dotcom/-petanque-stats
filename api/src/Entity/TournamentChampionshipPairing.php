<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TournamentChampionshipPairingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentChampionshipPairingRepository::class)]
#[ORM\Table(name: 'tournament_championship_pairings')]
#[ORM\UniqueConstraint(name: 'uniq_championship_pairing', columns: ['tournament_id', 'team_low_id', 'team_high_id'])]
#[ORM\UniqueConstraint(name: 'uniq_championship_pairing_match', columns: ['match_id'])]
class TournamentChampionshipPairing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tournament::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Tournament $tournament;

    #[ORM\OneToOne(targetEntity: TournamentMatch::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private TournamentMatch $match;

    #[ORM\Column(name: 'team_low_id', type: 'integer')]
    private int $teamLowId;

    #[ORM\Column(name: 'team_high_id', type: 'integer')]
    private int $teamHighId;

    public function __construct(TournamentMatch $match, int $teamLowId, int $teamHighId)
    {
        $this->match = $match;
        $this->tournament = $match->getTournament();
        $this->teamLowId = $teamLowId;
        $this->teamHighId = $teamHighId;
    }

    public function retarget(int $teamLowId, int $teamHighId): void
    {
        $this->teamLowId = $teamLowId;
        $this->teamHighId = $teamHighId;
    }
}
