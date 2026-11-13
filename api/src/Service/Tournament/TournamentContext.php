<?php

declare(strict_types=1);

namespace App\Service\Tournament;

use App\Entity\Tournament;
use App\Entity\TournamentDay;
use App\Entity\TournamentMatch;
use App\Entity\TournamentTeam;
use App\Entity\User;
use App\Repository\TournamentDayRepository;
use App\Repository\TournamentMatchRepository;
use App\Repository\TournamentMemberRepository;
use App\Repository\TournamentTeamPlayerRepository;
use App\Repository\TournamentTeamRepository;

final class TournamentContext
{
    public function __construct(
        private \App\Repository\TournamentRepository $tournaments,
        private TournamentMemberRepository $members,
        private TournamentTeamRepository $teams,
        private TournamentDayRepository $days,
        private TournamentMatchRepository $matches,
        private TournamentTeamPlayerRepository $teamPlayers,
    ) {
    }

    public function requireTournament(int $tournamentId): Tournament
    {
        $tournament = $this->tournaments->find($tournamentId);
        if (!$tournament instanceof Tournament) {
            throw new TournamentNotFoundException();
        }

        return $tournament;
    }

    public function requireMember(User $user, int $tournamentId): Tournament
    {
        $tournament = $this->requireTournament($tournamentId);
        if ($this->members->findOneByTournamentAndUser($tournament, $user) === null) {
            throw new TournamentNotFoundException();
        }

        return $tournament;
    }

    public function requireAdmin(User $user, int $tournamentId): Tournament
    {
        $tournament = $this->requireTournament($tournamentId);
        $member = $this->members->findOneByTournamentAndUser($tournament, $user);
        if ($member === null) {
            throw new TournamentNotFoundException();
        }
        if (!$member->isAdmin()) {
            throw new TournamentAccessException();
        }

        return $tournament;
    }

    public function requireTeam(Tournament $tournament, int $teamId): TournamentTeam
    {
        $team = $this->teams->findOneInTournament($tournament, $teamId);
        if (!$team instanceof TournamentTeam) {
            throw new TournamentRuleException('unknown_team', 'Team not found in this tournament.', 404);
        }

        return $team;
    }

    public function requireDay(Tournament $tournament, int $dayId): TournamentDay
    {
        $day = $this->days->findOneInTournament($tournament, $dayId);
        if (!$day instanceof TournamentDay) {
            throw new TournamentRuleException('unknown_day', 'Day not found in this tournament.', 404);
        }

        return $day;
    }

    public function requireMatch(Tournament $tournament, int $matchId): TournamentMatch
    {
        $match = $this->matches->findOneInTournament($tournament, $matchId);
        if (!$match instanceof TournamentMatch) {
            throw new TournamentRuleException('unknown_match', 'Match not found in this tournament.', 404);
        }

        return $match;
    }

    public function viewerTeamId(User $user, Tournament $tournament): ?int
    {
        $teamIds = $this->teamPlayers->findTeamIdsForUser($tournament, $user);
        if (count($teamIds) !== 1) {
            return null;
        }

        return $teamIds[0];
    }

    public function viewerIsAdmin(User $user, Tournament $tournament): bool
    {
        return $this->members->findOneByTournamentAndUser($tournament, $user)?->isAdmin() === true;
    }
}
