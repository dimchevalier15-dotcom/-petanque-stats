<?php

declare(strict_types=1);

namespace App\Service\Tournament;

use App\Dto\Request\GenerateTournamentDayRequest;
use App\Dto\Request\UpdateTournamentMatchLaneRequest;
use App\Dto\Request\UpdateTournamentMatchScoreRequest;
use App\Dto\Request\UpdateTournamentMatchTeamsRequest;
use App\Dto\Response\TournamentDayResponse;
use App\Dto\Response\TournamentDetailResponse;
use App\Dto\Response\TournamentMatchResponse;
use App\Entity\Tournament;
use App\Entity\TournamentDay;
use App\Entity\TournamentDayFakeTeam;
use App\Entity\TournamentDayTeamSlot;
use App\Entity\TournamentMatch;
use App\Entity\TournamentTeam;
use App\Entity\User;
use App\Enum\TournamentDayPhase;
use App\Enum\TournamentDayStatus;
use App\Enum\TournamentMatchPhase;
use App\Enum\TournamentPlayoffRound;
use App\Enum\TournamentStatus;
use App\Entity\TournamentChampionshipPairing;
use App\Repository\TournamentChampionshipPairingRepository;
use App\Repository\TournamentDayRepository;
use App\Repository\TournamentDayTeamSlotRepository;
use App\Repository\TournamentMatchRepository;
use App\Repository\TournamentRepository;
use App\Repository\TournamentTeamRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final class TournamentScheduleService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TournamentContext $context,
        private TournamentViewFactory $views,
        private TournamentRepository $tournaments,
        private TournamentTeamRepository $teams,
        private TournamentDayRepository $days,
        private TournamentMatchRepository $matches,
        private TournamentChampionshipPairingRepository $pairings,
        private TournamentDayTeamSlotRepository $slots,
        private TournamentLaneCatalog $lanes,
        private TournamentDayPairingPlanner $planner,
        private TournamentPlayoffBracket $bracket,
    ) {
    }

    public function ranking(User $user, int $tournamentId): \App\Dto\Response\TournamentRankingResponse
    {
        return $this->views->ranking($this->context->requireMember($user, $tournamentId));
    }

    public function generateDay(User $user, int $tournamentId, GenerateTournamentDayRequest $input): TournamentDayResponse
    {
        try {
            return $this->entityManager->wrapInTransaction(function () use ($user, $tournamentId, $input): TournamentDayResponse {
                $tournament = $this->context->requireAdmin($user, $tournamentId);
                $this->tournaments->lockById((int) $tournament->getId());
                $day = $this->buildChampionshipDay($tournament, $input);
                $this->markInProgress($tournament);
                $this->entityManager->flush();

                return $this->views->day($day, $this->context->viewerTeamId($user, $tournament), true);
            });
        } catch (UniqueConstraintViolationException) {
            throw new TournamentRuleException('conflict', 'This day duplicates matches that are already planned. Nothing was saved.');
        }
    }

    public function closeDay(User $user, int $tournamentId, int $dayId): TournamentDayResponse
    {
        $tournament = $this->context->requireAdmin($user, $tournamentId);
        $day = $this->context->requireDay($tournament, $dayId);
        if ($day->getStatus() === TournamentDayStatus::CLOSED) {
            throw new TournamentRuleException('day_already_closed', 'This day is already closed.');
        }
        foreach ($this->matches->findForDay($day) as $match) {
            if (!$match->isFictional() && !$match->hasScore()) {
                throw new TournamentRuleException('day_not_closable', 'Every real match needs a result before the day can be closed.');
            }
        }
        $day->close(new DateTimeImmutable());
        $this->entityManager->flush();

        return $this->views->day($day, $this->context->viewerTeamId($user, $tournament), true);
    }

    public function updateLane(User $user, int $tournamentId, int $matchId, UpdateTournamentMatchLaneRequest $input): TournamentMatchResponse
    {
        $tournament = $this->context->requireAdmin($user, $tournamentId);
        $match = $this->context->requireMatch($tournament, $matchId);
        $usable = $this->lanes->usableLaneNumbers($tournament->getNumberOfLanes(), $match->getDay()->getExcludedLaneNumbers());
        if (!in_array($input->laneNumber, $usable, true)) {
            throw new TournamentRuleException('lane_unavailable', 'This lane is not available for the day.', 422);
        }
        if ($this->matches->laneUsedOnDay($match->getDay(), $input->laneNumber, (int) $match->getId())) {
            throw new TournamentRuleException('lane_taken', 'This lane is already used by another match of the day.');
        }
        $match->setLaneNumber($input->laneNumber);
        $this->entityManager->flush();

        return $this->views->match($match, $this->context->viewerTeamId($user, $tournament), true);
    }

    public function updateScore(User $user, int $tournamentId, int $matchId, UpdateTournamentMatchScoreRequest $input): TournamentMatchResponse
    {
        try {
            return $this->entityManager->wrapInTransaction(function () use ($user, $tournamentId, $matchId, $input): TournamentMatchResponse {
                $tournament = $this->context->requireMember($user, $tournamentId);
                $this->tournaments->lockById((int) $tournament->getId());
                $match = $this->context->requireMatch($tournament, $matchId);
                $this->assertCanScore($user, $tournament, $match);
                [$score1, $score2] = $this->validatedScore($tournament, $match, $input);
                $match->recordScore($score1, $score2);
                $this->advancePlayoff($match);
                $this->refreshDayStatus($match->getDay());
                $this->refreshTournamentStatus($tournament);
                $this->entityManager->flush();

                return $this->views->match($match, $this->context->viewerTeamId($user, $tournament), $this->context->viewerIsAdmin($user, $tournament));
            });
        } catch (UniqueConstraintViolationException) {
            throw new TournamentRuleException('conflict', 'The result could not be saved because the bracket is no longer consistent. Nothing was saved.');
        }
    }

    public function updateTeams(User $user, int $tournamentId, int $matchId, UpdateTournamentMatchTeamsRequest $input): TournamentMatchResponse
    {
        try {
            return $this->entityManager->wrapInTransaction(function () use ($user, $tournamentId, $matchId, $input): TournamentMatchResponse {
                $tournament = $this->context->requireAdmin($user, $tournamentId);
                $this->tournaments->lockById((int) $tournament->getId());
                $match = $this->context->requireMatch($tournament, $matchId);
                $this->replaceChampionshipTeams($tournament, $match, $input->team1Id, $input->team2Id);
                $this->entityManager->flush();

                return $this->views->match($match, $this->context->viewerTeamId($user, $tournament), true);
            });
        } catch (UniqueConstraintViolationException) {
            throw new TournamentRuleException('conflict', 'This confrontation is already planned. Nothing was saved.');
        }
    }

    public function generatePlayoffs(User $user, int $tournamentId): TournamentDetailResponse
    {
        try {
            return $this->entityManager->wrapInTransaction(function () use ($user, $tournamentId): TournamentDetailResponse {
                $tournament = $this->context->requireAdmin($user, $tournamentId);
                $this->tournaments->lockById((int) $tournament->getId());
                $this->buildPlayoffs($tournament);

                return $this->views->detail($user, $tournament);
            });
        } catch (UniqueConstraintViolationException) {
            throw new TournamentRuleException('playoff_already_generated', 'The play-offs are already generated. Nothing was saved.');
        }
    }

    private function buildChampionshipDay(Tournament $tournament, GenerateTournamentDayRequest $input): TournamentDay
    {
        $byId = [];
        foreach ($this->teams->findForTournament($tournament) as $team) {
            $byId[(int) $team->getId()] = $team;
        }
        $absent = [];
        foreach ($input->absentTeamIds as $teamId) {
            if (!isset($byId[(int) $teamId])) {
                throw new TournamentRuleException('unknown_team', 'An absent team does not belong to this tournament.', 422);
            }
            $absent[(int) $teamId] = true;
        }
        $participating = [];
        foreach ($byId as $teamId => $team) {
            if (!isset($absent[$teamId])) {
                $participating[] = $teamId;
            }
        }
        $fakeNames = [];
        foreach ($input->fakeTeamNames as $name) {
            $trimmed = trim($name);
            if ($trimmed === '') {
                throw new TournamentRuleException('invalid_settings', 'A fictional team needs a name.', 422);
            }
            $fakeNames[] = $trimmed;
        }
        if (count($participating) + count($fakeNames) < 2) {
            throw new TournamentRuleException('not_enough_teams', 'At least two teams are required to generate a day.');
        }
        $excluded = $this->lanes->normalizeExcluded($input->excludedLaneNumbers);
        try {
            $planned = $this->planner->plan($participating, array_keys($fakeNames), $this->pairings->pairKeys($tournament));
        } catch (PairingImpossibleException) {
            throw new TournamentRuleException('no_valid_pairing', 'No valid day can be generated with the selected teams and the confrontations already planned.');
        }
        $usable = $this->lanes->usableLaneNumbers($tournament->getNumberOfLanes(), $excluded);
        if (count($usable) < count($planned)) {
            throw new TournamentRuleException('not_enough_lanes', 'There are not enough available lanes for the matches of this day.');
        }
        shuffle($usable);
        $day = new TournamentDay(
            $tournament,
            $this->days->nextNumber($tournament, TournamentDayPhase::CHAMPIONSHIP),
            TournamentDayPhase::CHAMPIONSHIP,
            $excluded,
        );
        $this->entityManager->persist($day);
        $fakeTeams = [];
        foreach ($fakeNames as $index => $fakeName) {
            $fakeTeam = new TournamentDayFakeTeam($day, $fakeName);
            $this->entityManager->persist($fakeTeam);
            $fakeTeams[$index] = $fakeTeam;
        }
        $this->entityManager->flush();
        foreach ($planned as $index => $plan) {
            $match = new TournamentMatch(
                $day,
                $usable[$index],
                TournamentMatchPhase::CHAMPIONSHIP,
                $plan->isFictional(),
            );
            $match->assignSides(
                $plan->realTeam1Id !== null ? $byId[$plan->realTeam1Id] : null,
                $plan->realTeam2Id !== null ? $byId[$plan->realTeam2Id] : null,
                $plan->fakeTeam1Index !== null ? $fakeTeams[$plan->fakeTeam1Index] : null,
                $plan->fakeTeam2Index !== null ? $fakeTeams[$plan->fakeTeam2Index] : null,
            );
            $this->entityManager->persist($match);
            $this->syncPairing($match);
            $this->occupy($match);
        }

        return $day;
    }

    private function buildPlayoffs(Tournament $tournament): void
    {
        if ($this->matches->hasPlayoffMatches($tournament)) {
            throw new TournamentRuleException('playoff_already_generated', 'The play-offs are already generated.');
        }
        if ($this->days->countChampionshipDays($tournament) < 1 || $this->days->hasOpenChampionshipDay($tournament)) {
            throw new TournamentRuleException('playoff_not_ready', 'Close every championship day before generating the play-offs.');
        }
        $qualified = [];
        foreach ($this->views->ranking($tournament)->rows as $row) {
            if ($row->qualifiedForPlayoffs) {
                $qualified[$row->position] = $row->teamId;
            }
        }
        ksort($qualified);
        $plans = $this->bracket->plan(array_values($qualified));
        $usable = $this->lanes->usableLaneNumbers($tournament->getNumberOfLanes(), $tournament->getExcludedLaneNumbers());
        if (count($usable) < 4) {
            throw new TournamentRuleException('not_enough_lanes', 'Four lanes are required to generate the quarter-finals.');
        }
        $teams = [];
        foreach ($this->teams->findForTournament($tournament) as $team) {
            $teams[(int) $team->getId()] = $team;
        }
        $number = $this->days->nextNumber($tournament, TournamentDayPhase::PLAYOFF);
        $quarterDay = $this->playoffDay($tournament, $number, TournamentPlayoffRound::QUARTER);
        $semiDay = $this->playoffDay($tournament, $number + 1, TournamentPlayoffRound::SEMI);
        $finalDay = $this->playoffDay($tournament, $number + 2, TournamentPlayoffRound::FINAL);
        $quarters = $this->createRound($quarterDay, $plans, TournamentPlayoffRound::QUARTER, $usable, $teams, []);
        $this->entityManager->flush();
        $semis = $this->createRound($semiDay, $plans, TournamentPlayoffRound::SEMI, $usable, $teams, $quarters);
        $this->entityManager->flush();
        $this->createRound($finalDay, $plans, TournamentPlayoffRound::FINAL, $usable, $teams, $semis);
        $this->markInProgress($tournament);
        $this->entityManager->flush();
    }

    /**
     * @param list<\App\Service\Tournament\PlannedPlayoffMatch> $plans
     * @param array<int, TournamentTeam> $teams
     * @param array<int, TournamentMatch> $sources
     * @return array<int, TournamentMatch>
     */
    private function createRound(
        TournamentDay $day,
        array $plans,
        TournamentPlayoffRound $round,
        array $usable,
        array $teams,
        array $sources,
    ): array {
        $lanes = $usable;
        shuffle($lanes);
        $created = [];
        $laneIndex = 0;
        foreach ($plans as $plan) {
            if ($plan->round !== $round) {
                continue;
            }
            $match = new TournamentMatch($day, $lanes[$laneIndex], TournamentMatchPhase::PLAYOFF, false, $round, $plan->slot);
            ++$laneIndex;
            $team1 = $plan->team1Id !== null ? $teams[$plan->team1Id] : null;
            $team2 = $plan->team2Id !== null ? $teams[$plan->team2Id] : null;
            $match->assignSides($team1, $team2, null, null);
            if ($plan->homeSourceSlot !== null && $plan->awaySourceSlot !== null) {
                $match->setSources($sources[$plan->homeSourceSlot], $sources[$plan->awaySourceSlot]);
            }
            $this->entityManager->persist($match);
            $this->occupy($match);
            $created[$plan->slot] = $match;
        }

        return $created;
    }

    private function playoffDay(Tournament $tournament, int $number, TournamentPlayoffRound $round): TournamentDay
    {
        $day = new TournamentDay(
            $tournament,
            $number,
            TournamentDayPhase::PLAYOFF,
            $tournament->getExcludedLaneNumbers(),
            $round,
        );
        $this->entityManager->persist($day);

        return $day;
    }

    private function replaceChampionshipTeams(Tournament $tournament, TournamentMatch $match, int $team1Id, int $team2Id): void
    {
        if ($match->getPhase() !== TournamentMatchPhase::CHAMPIONSHIP) {
            throw new TournamentRuleException('playoff_sides_fixed', 'Play-off matches follow the bracket and cannot be edited team by team.');
        }
        if ($match->isFictional()) {
            throw new TournamentRuleException('fictional_sides_fixed', 'A fictional match keeps the teams chosen when the day was generated.');
        }
        if ($team1Id === $team2Id) {
            throw new TournamentRuleException('invalid_settings', 'A match needs two different teams.', 422);
        }
        $team1 = $this->context->requireTeam($tournament, $team1Id);
        $team2 = $this->context->requireTeam($tournament, $team2Id);
        $low = min($team1Id, $team2Id);
        $high = max($team1Id, $team2Id);
        if ($this->pairings->exists($tournament, $low, $high, (int) $match->getId())) {
            throw new TournamentRuleException('duplicate_pairing', 'These two teams are already scheduled to play in the championship.');
        }
        if ($this->slots->teamPlaysOnDay($match->getDay(), $team1, (int) $match->getId())
            || $this->slots->teamPlaysOnDay($match->getDay(), $team2, (int) $match->getId())) {
            throw new TournamentRuleException('team_already_playing', 'A real team cannot play two matches on the same day.');
        }
        $this->slots->deleteForMatch($match);
        $this->entityManager->flush();
        $match->assignSides($team1, $team2, null, null);
        $this->syncPairing($match);
        $this->occupy($match);
    }

    private function syncPairing(TournamentMatch $match): void
    {
        $low = $match->getChampionshipTeamLowId();
        $high = $match->getChampionshipTeamHighId();
        if ($match->getId() === null) {
            if ($low !== null && $high !== null) {
                $this->entityManager->persist(new TournamentChampionshipPairing($match, $low, $high));
            }

            return;
        }
        $existing = $this->pairings->findOneByMatch($match);
        if ($low === null || $high === null) {
            if ($existing !== null) {
                $this->entityManager->remove($existing);
            }

            return;
        }
        if ($existing === null) {
            $this->entityManager->persist(new TournamentChampionshipPairing($match, $low, $high));

            return;
        }
        $existing->retarget($low, $high);
    }

    private function assertCanScore(User $user, Tournament $tournament, TournamentMatch $match): void
    {
        $leftReady = $match->getTeam1() !== null || $match->getFakeTeam1() !== null;
        $rightReady = $match->getTeam2() !== null || $match->getFakeTeam2() !== null;
        if (!$leftReady || !$rightReady) {
            throw new TournamentRuleException('match_not_ready', 'Both teams must be known before a score is saved.', 422);
        }
        if ($this->context->viewerIsAdmin($user, $tournament)) {
            return;
        }
        if ($match->getDay()->getStatus() === TournamentDayStatus::CLOSED) {
            throw new TournamentRuleException('day_closed', 'This day is closed. Only an administrator can change the score.', 403);
        }
        $teamId = $this->context->viewerTeamId($user, $tournament);
        $playing = array_values(array_filter([
            $match->getTeam1()?->getId(),
            $match->getTeam2()?->getId(),
        ]));
        if ($teamId === null || !in_array($teamId, $playing, true)) {
            throw new TournamentAccessException();
        }
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    private function validatedScore(Tournament $tournament, TournamentMatch $match, UpdateTournamentMatchScoreRequest $input): array
    {
        if ($input->clear) {
            return [null, null];
        }
        if ($input->score1 === null || $input->score2 === null) {
            throw new TournamentRuleException('score_required', 'Both scores are required.', 422);
        }
        if ($input->score1 < 0 || $input->score1 > 13 || $input->score2 < 0 || $input->score2 > 13) {
            throw new TournamentRuleException('score_out_of_range', 'Scores must be between 0 and 13.', 422);
        }
        if ($input->score1 === $input->score2 && $match->getPhase() === TournamentMatchPhase::PLAYOFF) {
            throw new TournamentRuleException('playoff_draw', 'Play-off matches cannot end in a draw.', 422);
        }
        if ($input->score1 === $input->score2 && !$tournament->drawsAllowed()) {
            throw new TournamentRuleException('draws_not_allowed', 'Draws are not allowed in this tournament.', 422);
        }

        return [$input->score1, $input->score2];
    }

    private function advancePlayoff(TournamentMatch $match): void
    {
        if ($match->getPhase() !== TournamentMatchPhase::PLAYOFF) {
            return;
        }
        $fed = $this->matches->findFedBy($match);
        if ($fed === null) {
            return;
        }
        $home = $fed->getHomeSourceMatch()?->getId() === $match->getId();
        $winner = $match->winnerTeam();
        $current = $home ? $fed->getTeam1() : $fed->getTeam2();
        if ($current?->getId() === $winner?->getId()) {
            return;
        }
        $this->assertDownstreamOpen($fed);
        $this->slots->deleteForMatch($fed);
        $this->entityManager->flush();
        if ($home) {
            $fed->assignSides($winner, $fed->getTeam2(), null, null);
        } else {
            $fed->assignSides($fed->getTeam1(), $winner, null, null);
        }
        $this->occupy($fed);
    }

    private function assertDownstreamOpen(TournamentMatch $match): void
    {
        if ($match->hasScore()) {
            throw new TournamentRuleException('match_locked', 'A later play-off match already has a result.');
        }
        $next = $this->matches->findFedBy($match);
        if ($next !== null) {
            $this->assertDownstreamOpen($next);
        }
    }

    private function occupy(TournamentMatch $match): void
    {
        foreach ([$match->getTeam1(), $match->getTeam2()] as $team) {
            if ($team instanceof TournamentTeam) {
                $this->entityManager->persist(new TournamentDayTeamSlot($match->getDay(), $team, $match));
            }
        }
    }

    private function refreshDayStatus(TournamentDay $day): void
    {
        if ($day->getStatus() === TournamentDayStatus::CLOSED) {
            return;
        }
        $started = false;
        foreach ($this->matches->findForDay($day) as $match) {
            if (!$match->isFictional() && $match->hasScore()) {
                $started = true;
                break;
            }
        }
        $day->setStatus($started ? TournamentDayStatus::IN_PROGRESS : TournamentDayStatus::GENERATED);
    }

    private function refreshTournamentStatus(Tournament $tournament): void
    {
        if ($this->matches->findFinal($tournament)?->hasScore() === true) {
            $tournament->setStatus(TournamentStatus::FINISHED);

            return;
        }
        if ($this->days->countForTournament($tournament) > 0) {
            $tournament->setStatus(TournamentStatus::IN_PROGRESS);

            return;
        }
        $tournament->setStatus(TournamentStatus::UPCOMING);
    }

    private function markInProgress(Tournament $tournament): void
    {
        if ($tournament->getStatus() === TournamentStatus::UPCOMING) {
            $tournament->setStatus(TournamentStatus::IN_PROGRESS);
        }
    }
}
