<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TournamentMatchPhase;
use App\Enum\TournamentMatchStatus;
use App\Enum\TournamentPlayoffRound;
use App\Repository\TournamentMatchRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentMatchRepository::class)]
#[ORM\Table(name: 'tournament_matches')]
#[ORM\Index(name: 'idx_tournament_match_day', columns: ['day_id'])]
#[ORM\Index(name: 'idx_tournament_match_phase', columns: ['tournament_id', 'phase'])]
class TournamentMatch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tournament::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Tournament $tournament;

    #[ORM\ManyToOne(targetEntity: TournamentDay::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private TournamentDay $day;

    #[ORM\ManyToOne(targetEntity: TournamentTeam::class)]
    #[ORM\JoinColumn(name: 'team1_id', nullable: true, onDelete: 'RESTRICT')]
    private ?TournamentTeam $team1 = null;

    #[ORM\ManyToOne(targetEntity: TournamentTeam::class)]
    #[ORM\JoinColumn(name: 'team2_id', nullable: true, onDelete: 'RESTRICT')]
    private ?TournamentTeam $team2 = null;

    #[ORM\ManyToOne(targetEntity: TournamentDayFakeTeam::class)]
    #[ORM\JoinColumn(name: 'fake_team1_id', nullable: true, onDelete: 'CASCADE')]
    private ?TournamentDayFakeTeam $fakeTeam1 = null;

    #[ORM\ManyToOne(targetEntity: TournamentDayFakeTeam::class)]
    #[ORM\JoinColumn(name: 'fake_team2_id', nullable: true, onDelete: 'CASCADE')]
    private ?TournamentDayFakeTeam $fakeTeam2 = null;

    #[ORM\Column(name: 'championship_team_low_id', type: 'integer', nullable: true)]
    private ?int $championshipTeamLowId = null;

    #[ORM\Column(name: 'championship_team_high_id', type: 'integer', nullable: true)]
    private ?int $championshipTeamHighId = null;

    #[ORM\Column(name: 'score1', type: 'integer', nullable: true)]
    private ?int $score1 = null;

    #[ORM\Column(name: 'score2', type: 'integer', nullable: true)]
    private ?int $score2 = null;

    #[ORM\Column(name: 'lane_number', type: 'integer')]
    private int $laneNumber;

    #[ORM\Column(type: 'string', length: 20, enumType: TournamentMatchStatus::class)]
    private TournamentMatchStatus $status;

    #[ORM\Column(type: 'boolean')]
    private bool $fictional;

    #[ORM\Column(type: 'string', length: 20, enumType: TournamentMatchPhase::class)]
    private TournamentMatchPhase $phase;

    #[ORM\Column(name: 'playoff_round', type: 'string', length: 20, nullable: true, enumType: TournamentPlayoffRound::class)]
    private ?TournamentPlayoffRound $playoffRound;

    #[ORM\Column(name: 'playoff_slot', type: 'integer', nullable: true)]
    private ?int $playoffSlot;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'home_source_match_id', nullable: true, onDelete: 'SET NULL')]
    private ?TournamentMatch $homeSourceMatch = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'away_source_match_id', nullable: true, onDelete: 'SET NULL')]
    private ?TournamentMatch $awaySourceMatch = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    public function __construct(
        TournamentDay $day,
        int $laneNumber,
        TournamentMatchPhase $phase,
        bool $fictional,
        ?TournamentPlayoffRound $playoffRound = null,
        ?int $playoffSlot = null,
    ) {
        $this->tournament = $day->getTournament();
        $this->day = $day;
        $this->laneNumber = $laneNumber;
        $this->phase = $phase;
        $this->fictional = $fictional;
        $this->playoffRound = $playoffRound;
        $this->playoffSlot = $playoffSlot;
        $this->status = TournamentMatchStatus::SCHEDULED;
        $now = new DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTournament(): Tournament
    {
        return $this->tournament;
    }

    public function getDay(): TournamentDay
    {
        return $this->day;
    }

    public function getTeam1(): ?TournamentTeam
    {
        return $this->team1;
    }

    public function getTeam2(): ?TournamentTeam
    {
        return $this->team2;
    }

    public function getFakeTeam1(): ?TournamentDayFakeTeam
    {
        return $this->fakeTeam1;
    }

    public function getFakeTeam2(): ?TournamentDayFakeTeam
    {
        return $this->fakeTeam2;
    }

    public function getChampionshipTeamLowId(): ?int
    {
        return $this->championshipTeamLowId;
    }

    public function getChampionshipTeamHighId(): ?int
    {
        return $this->championshipTeamHighId;
    }

    public function getScore1(): ?int
    {
        return $this->score1;
    }

    public function getScore2(): ?int
    {
        return $this->score2;
    }

    public function getLaneNumber(): int
    {
        return $this->laneNumber;
    }

    public function setLaneNumber(int $laneNumber): void
    {
        $this->laneNumber = $laneNumber;
        $this->touch();
    }

    public function getStatus(): TournamentMatchStatus
    {
        return $this->status;
    }

    public function isFictional(): bool
    {
        return $this->fictional;
    }

    public function getPhase(): TournamentMatchPhase
    {
        return $this->phase;
    }

    public function getPlayoffRound(): ?TournamentPlayoffRound
    {
        return $this->playoffRound;
    }

    public function getPlayoffSlot(): ?int
    {
        return $this->playoffSlot;
    }

    public function getHomeSourceMatch(): ?TournamentMatch
    {
        return $this->homeSourceMatch;
    }

    public function getAwaySourceMatch(): ?TournamentMatch
    {
        return $this->awaySourceMatch;
    }

    public function setSources(?TournamentMatch $homeSourceMatch, ?TournamentMatch $awaySourceMatch): void
    {
        $this->homeSourceMatch = $homeSourceMatch;
        $this->awaySourceMatch = $awaySourceMatch;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function hasScore(): bool
    {
        return $this->score1 !== null && $this->score2 !== null;
    }

    public function assignSides(
        ?TournamentTeam $team1,
        ?TournamentTeam $team2,
        ?TournamentDayFakeTeam $fakeTeam1,
        ?TournamentDayFakeTeam $fakeTeam2,
    ): void {
        $this->team1 = $team1;
        $this->team2 = $team2;
        $this->fakeTeam1 = $fakeTeam1;
        $this->fakeTeam2 = $fakeTeam2;
        $this->fictional = $fakeTeam1 !== null || $fakeTeam2 !== null;
        $this->refreshChampionshipPair();
        $this->touch();
    }

    public function recordScore(?int $score1, ?int $score2): void
    {
        $this->score1 = $score1;
        $this->score2 = $score2;
        $this->status = $score1 !== null && $score2 !== null
            ? TournamentMatchStatus::FINISHED
            : TournamentMatchStatus::SCHEDULED;
        $this->touch();
    }

    public function winnerTeam(): ?TournamentTeam
    {
        if (!$this->hasScore() || $this->score1 === $this->score2) {
            return null;
        }

        return $this->score1 > $this->score2 ? $this->team1 : $this->team2;
    }

    private function refreshChampionshipPair(): void
    {
        $team1Id = $this->team1?->getId();
        $team2Id = $this->team2?->getId();
        $countsForChampionship = $this->phase === TournamentMatchPhase::CHAMPIONSHIP
            && !$this->fictional
            && $team1Id !== null
            && $team2Id !== null;
        if (!$countsForChampionship) {
            $this->championshipTeamLowId = null;
            $this->championshipTeamHighId = null;

            return;
        }
        $this->championshipTeamLowId = min($team1Id, $team2Id);
        $this->championshipTeamHighId = max($team1Id, $team2Id);
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
