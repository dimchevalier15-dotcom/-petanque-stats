<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TournamentDayPhase;
use App\Enum\TournamentDayStatus;
use App\Enum\TournamentPlayoffRound;
use App\Repository\TournamentDayRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentDayRepository::class)]
#[ORM\Table(name: 'tournament_days')]
#[ORM\UniqueConstraint(name: 'uniq_tournament_day_number', columns: ['tournament_id', 'phase', 'number'])]
class TournamentDay
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tournament::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Tournament $tournament;

    #[ORM\Column(type: 'integer')]
    private int $number;

    #[ORM\Column(type: 'string', length: 20, enumType: TournamentDayPhase::class)]
    private TournamentDayPhase $phase;

    #[ORM\Column(type: 'string', length: 20, enumType: TournamentDayStatus::class)]
    private TournamentDayStatus $status;

    #[ORM\Column(name: 'playoff_round', type: 'string', length: 20, nullable: true, enumType: TournamentPlayoffRound::class)]
    private ?TournamentPlayoffRound $playoffRound;

    /**
     * @var list<int>
     */
    #[ORM\Column(name: 'excluded_lane_numbers', type: 'json')]
    private array $excludedLaneNumbers;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'closed_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $closedAt = null;

    /**
     * @param list<int> $excludedLaneNumbers
     */
    public function __construct(
        Tournament $tournament,
        int $number,
        TournamentDayPhase $phase,
        array $excludedLaneNumbers,
        ?TournamentPlayoffRound $playoffRound = null,
    ) {
        $this->tournament = $tournament;
        $this->number = $number;
        $this->phase = $phase;
        $this->status = TournamentDayStatus::GENERATED;
        $this->playoffRound = $playoffRound;
        $this->excludedLaneNumbers = $excludedLaneNumbers;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTournament(): Tournament
    {
        return $this->tournament;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getPhase(): TournamentDayPhase
    {
        return $this->phase;
    }

    public function getStatus(): TournamentDayStatus
    {
        return $this->status;
    }

    public function setStatus(TournamentDayStatus $status): void
    {
        $this->status = $status;
    }

    public function getPlayoffRound(): ?TournamentPlayoffRound
    {
        return $this->playoffRound;
    }

    /**
     * @return list<int>
     */
    public function getExcludedLaneNumbers(): array
    {
        $lanes = [];
        foreach ($this->excludedLaneNumbers as $lane) {
            $lanes[] = (int) $lane;
        }

        return $lanes;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getClosedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function close(DateTimeImmutable $closedAt): void
    {
        $this->status = TournamentDayStatus::CLOSED;
        $this->closedAt = $closedAt;
    }
}
