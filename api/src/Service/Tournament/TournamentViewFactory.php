<?php

declare(strict_types=1);

namespace App\Service\Tournament;

use App\Dto\Response\TournamentDayResponse;
use App\Dto\Response\TournamentDetailResponse;
use App\Dto\Response\TournamentMatchResponse;
use App\Dto\Response\TournamentMatchSideResponse;
use App\Dto\Response\TournamentMemberResponse;
use App\Dto\Response\TournamentRankingResponse;
use App\Dto\Response\TournamentStandingResponse;
use App\Dto\Response\TournamentSummaryResponse;
use App\Dto\Response\TournamentTeamPlayerResponse;
use App\Dto\Response\TournamentTeamResponse;
use App\Entity\Tournament;
use App\Entity\TournamentDay;
use App\Entity\TournamentDayFakeTeam;
use App\Entity\TournamentMatch;
use App\Entity\TournamentTeam;
use App\Entity\User;
use App\Enum\TournamentDayPhase;
use App\Enum\TournamentDayStatus;
use App\Enum\TournamentMatchPhase;
use App\Repository\TournamentDayRepository;
use App\Repository\TournamentMatchRepository;
use App\Repository\TournamentMemberRepository;
use App\Repository\TournamentTeamPlayerRepository;
use App\Repository\TournamentTeamRepository;

final class TournamentViewFactory
{
    public function __construct(
        private TournamentTeamRepository $teams,
        private TournamentTeamPlayerRepository $teamPlayers,
        private TournamentMemberRepository $members,
        private TournamentDayRepository $days,
        private TournamentMatchRepository $matches,
        private TournamentLaneCatalog $lanes,
        private TournamentRankingCalculator $ranking,
        private TournamentContext $context,
    ) {
    }

    public function summary(Tournament $tournament): TournamentSummaryResponse
    {
        return new TournamentSummaryResponse(
            (int) $tournament->getId(),
            $tournament->getName(),
            $tournament->getDescription(),
            $tournament->getStatus()->value,
            $tournament->getFormat()->value,
            $tournament->getStartsAt()?->format('Y-m-d'),
            $tournament->getEndsAt()?->format('Y-m-d'),
        );
    }

    public function detail(User $user, Tournament $tournament): TournamentDetailResponse
    {
        $viewerIsAdmin = $this->context->viewerIsAdmin($user, $tournament);
        $viewerTeamId = $this->context->viewerTeamId($user, $tournament);
        $summary = $this->summary($tournament);

        return $this->buildDetailResponse(
            $tournament,
            $summary,
            $viewerIsAdmin,
            $viewerTeamId,
            $viewerIsAdmin ? $this->memberResponses($tournament) : null,
        );
    }

    public function publicDetail(Tournament $tournament): TournamentDetailResponse
    {
        $summary = $this->summary($tournament);

        return $this->buildDetailResponse($tournament, $summary, false, null, null);
    }

    /**
     * @param list<TournamentMemberResponse>|null $members
     */
    private function buildDetailResponse(
        Tournament $tournament,
        TournamentSummaryResponse $summary,
        bool $viewerIsAdmin,
        ?int $viewerTeamId,
        ?array $members,
    ): TournamentDetailResponse {
        return new TournamentDetailResponse(
            $summary->id,
            $tournament->getPublicUuid(),
            $summary->name,
            $summary->description,
            $summary->status,
            $summary->format,
            $summary->startsAt,
            $summary->endsAt,
            $tournament->getMinPlayersPerTeam(),
            $tournament->getMaxPlayersPerTeam(),
            $tournament->getPointsForWin(),
            $tournament->getPointsForDraw(),
            $tournament->drawsAllowed(),
            $tournament->getNumberOfLanes(),
            $tournament->getExcludedLaneNumbers(),
            $viewerIsAdmin,
            $viewerTeamId,
            $this->matches->hasPlayoffMatches($tournament),
            $this->teamResponses($tournament),
            $members,
            $this->dayResponses($tournament, $viewerTeamId, $viewerIsAdmin),
            $this->ranking($tournament),
        );
    }

    public function day(TournamentDay $day, ?int $viewerTeamId, bool $viewerIsAdmin): TournamentDayResponse
    {
        $responses = $this->dayResponses($day->getTournament(), $viewerTeamId, $viewerIsAdmin);
        foreach ($responses as $response) {
            if ($response->id === (int) $day->getId()) {
                return $response;
            }
        }

        throw new TournamentNotFoundException();
    }

    public function match(TournamentMatch $match, ?int $viewerTeamId, bool $viewerIsAdmin): TournamentMatchResponse
    {
        return $this->matchResponse($match, $viewerTeamId, $viewerIsAdmin);
    }

    public function ranking(Tournament $tournament): TournamentRankingResponse
    {
        $teamInputs = [];
        foreach ($this->teams->findForTournament($tournament) as $team) {
            $teamInputs[] = new RankingTeamInput((int) $team->getId(), $team->getName());
        }
        $matchInputs = [];
        foreach ($this->matches->findScoredChampionshipMatches($tournament) as $match) {
            $team1 = $match->getTeam1();
            $team2 = $match->getTeam2();
            if ($team1 === null || $team2 === null || !$match->hasScore()) {
                continue;
            }
            $matchInputs[] = new RankingMatchInput(
                (int) $team1->getId(),
                (int) $team2->getId(),
                (int) $match->getScore1(),
                (int) $match->getScore2(),
            );
        }
        $standings = $this->ranking->rank(
            $teamInputs,
            $matchInputs,
            $tournament->getPointsForWin(),
            $tournament->getPointsForDraw(),
        );
        $teamCount = count($standings);
        $qualifyCutoff = min(8, $teamCount);
        $canQualify = $teamCount >= 2;
        $tieUnresolved = false;
        $rows = [];
        foreach ($standings as $standing) {
            if ($standing->tieUnresolved) {
                $tieUnresolved = true;
            }
            $rows[] = new TournamentStandingResponse(
                $standing->position,
                $standing->teamId,
                $standing->teamName,
                $standing->played,
                $standing->wins,
                $standing->draws,
                $standing->losses,
                $standing->points,
                $standing->pointsFor,
                $standing->pointsAgainst,
                $standing->pointsDifference,
                $standing->tieUnresolved,
                $canQualify && $standing->position <= $qualifyCutoff,
            );
        }

        return new TournamentRankingResponse($rows, $tieUnresolved);
    }

    /**
     * @return list<TournamentTeamResponse>
     */
    private function teamResponses(Tournament $tournament): array
    {
        $responses = [];
        foreach ($this->teams->findForTournament($tournament) as $team) {
            $responses[] = $this->teamResponse($team);
        }

        return $responses;
    }

    public function teamResponse(TournamentTeam $team): TournamentTeamResponse
    {
        $players = [];
        foreach ($this->teamPlayers->findForTeam($team) as $membership) {
            $player = $membership->getPlayer();
            $players[] = new TournamentTeamPlayerResponse(
                (int) $player->getId(),
                $player->getFirstName(),
                $player->getLastName(),
                $player->getNickname(),
                $player->getUser()?->getId(),
            );
        }

        return new TournamentTeamResponse((int) $team->getId(), $team->getName(), $players);
    }

    /**
     * @return list<TournamentMemberResponse>
     */
    private function memberResponses(Tournament $tournament): array
    {
        $responses = [];
        foreach ($this->members->findForTournament($tournament) as $member) {
            $responses[] = new TournamentMemberResponse(
                (int) $member->getUser()->getId(),
                $member->getUser()->getEmail(),
                $member->isAdmin(),
                $member->getCreatedAt()->format(DATE_ATOM),
            );
        }

        return $responses;
    }

    /**
     * @return list<TournamentDayResponse>
     */
    private function dayResponses(Tournament $tournament, ?int $viewerTeamId, bool $viewerIsAdmin): array
    {
        $responses = [];
        foreach ($this->days->findForTournament($tournament) as $day) {
            $matches = [];
            $readyToClose = $day->getStatus()->value !== 'closed';
            foreach ($this->matches->findForDay($day) as $match) {
                $matches[] = $this->matchResponse($match, $viewerTeamId, $viewerIsAdmin);
            }
            usort($matches, static function (TournamentMatchResponse $left, TournamentMatchResponse $right): int {
                if ($left->highlighted !== $right->highlighted) {
                    return $left->highlighted ? -1 : 1;
                }

                return $left->laneNumber <=> $right->laneNumber;
            });
            $responses[] = new TournamentDayResponse(
                (int) $day->getId(),
                $day->getNumber(),
                $day->getPhase()->value,
                $day->getStatus()->value,
                $day->getPlayoffRound()?->value,
                $day->getClosedAt()?->format(DATE_ATOM),
                $day->getExcludedLaneNumbers(),
                $this->lanes->usableLaneNumbers($tournament->getNumberOfLanes(), $day->getExcludedLaneNumbers()),
                $readyToClose && $matches !== [],
                $this->dayIsDeletable($tournament, $day),
                $matches,
            );
        }

        return $responses;
    }

    private function dayIsDeletable(Tournament $tournament, TournamentDay $day): bool
    {
        if ($day->getPhase() !== TournamentDayPhase::CHAMPIONSHIP) {
            return false;
        }
        if ($day->getStatus() !== TournamentDayStatus::GENERATED) {
            return false;
        }
        if ($this->matches->hasPlayoffMatches($tournament)) {
            return false;
        }
        foreach ($this->matches->findForDay($day) as $match) {
            if ($match->hasScore()) {
                return false;
            }
        }

        return true;
    }

    private function matchResponse(TournamentMatch $match, ?int $viewerTeamId, bool $viewerIsAdmin): TournamentMatchResponse
    {
        $team1Id = $match->getTeam1()?->getId();
        $team2Id = $match->getTeam2()?->getId();
        $highlighted = $viewerTeamId !== null && ($viewerTeamId === $team1Id || $viewerTeamId === $team2Id);
        $leftReady = $match->getTeam1() !== null || $match->getFakeTeam1() !== null;
        $rightReady = $match->getTeam2() !== null || $match->getFakeTeam2() !== null;
        $dayClosed = $match->getDay()->getStatus() === TournamentDayStatus::CLOSED;
        $canScore = $leftReady && $rightReady && ($viewerIsAdmin || ($highlighted && !$dayClosed));

        return new TournamentMatchResponse(
            (int) $match->getId(),
            $this->side($match->getTeam1(), $match->getFakeTeam1(), $match->getHomeSourceMatch()),
            $this->side($match->getTeam2(), $match->getFakeTeam2(), $match->getAwaySourceMatch()),
            $match->getScore1(),
            $match->getScore2(),
            $match->getLaneNumber(),
            $match->getStatus()->value,
            $match->isFictional(),
            $match->getPhase()->value,
            $match->getPlayoffRound()?->value,
            $match->getPlayoffSlot(),
            $highlighted,
            $canScore,
        );
    }

    private function side(
        ?TournamentTeam $team,
        ?TournamentDayFakeTeam $fakeTeam,
        ?TournamentMatch $source,
    ): TournamentMatchSideResponse {
        if ($team !== null) {
            return new TournamentMatchSideResponse((int) $team->getId(), null, $team->getName(), false, null, null);
        }
        if ($fakeTeam !== null) {
            return new TournamentMatchSideResponse(null, (int) $fakeTeam->getId(), $fakeTeam->getName(), true, null, null);
        }
        if ($source !== null && $source->getPhase() === TournamentMatchPhase::PLAYOFF) {
            return new TournamentMatchSideResponse(
                null,
                null,
                null,
                false,
                $source->getPlayoffRound()?->value,
                $source->getPlayoffSlot(),
            );
        }

        return new TournamentMatchSideResponse(null, null, null, false, null, null);
    }
}
