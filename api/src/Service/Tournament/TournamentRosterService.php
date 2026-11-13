<?php

declare(strict_types=1);

namespace App\Service\Tournament;

use App\Dto\Request\AddTournamentMemberRequest;
use App\Dto\Request\AddTournamentTeamPlayerRequest;
use App\Dto\Request\CreateTournamentTeamRequest;
use App\Dto\Request\RenameTournamentTeamRequest;
use App\Dto\Response\TournamentDetailResponse;
use App\Entity\Player;
use App\Entity\Tournament;
use App\Entity\TournamentMember;
use App\Entity\TournamentTeam;
use App\Entity\TournamentTeamPlayer;
use App\Entity\User;
use App\Repository\PlayerRepository;
use App\Repository\TournamentMatchRepository;
use App\Repository\TournamentMemberRepository;
use App\Repository\TournamentTeamPlayerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final class TournamentRosterService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TournamentContext $context,
        private TournamentViewFactory $views,
        private UserRepository $users,
        private PlayerRepository $players,
        private TournamentMemberRepository $members,
        private TournamentTeamPlayerRepository $teamPlayers,
        private TournamentMatchRepository $matches,
        private TournamentMembershipSyncService $membershipSync,
    ) {
    }

    public function addMember(User $actor, int $tournamentId, AddTournamentMemberRequest $input): TournamentDetailResponse
    {
        if ($input->admin && !$actor->isMaster()) {
            throw new TournamentAccessException();
        }
        $tournament = $input->admin
            ? $this->context->requireTournament($tournamentId)
            : $this->context->requireAdmin($actor, $tournamentId);
        if ($input->admin && !$actor->isMaster()) {
            throw new TournamentAccessException();
        }
        $user = $this->users->find($input->userId);
        if (!$user instanceof User) {
            throw new TournamentRuleException('user_not_found', 'User not found.', 422);
        }
        if ($this->members->findOneByTournamentAndUser($tournament, $user) !== null) {
            throw new TournamentRuleException('member_exists', 'This user is already a member of the tournament.', 409);
        }
        $this->entityManager->persist(new TournamentMember($tournament, $user, $input->admin));
        $this->entityManager->flush();

        return $this->views->detail($this->viewer($actor, $tournament), $tournament);
    }

    public function updateMember(User $actor, int $tournamentId, int $userId, bool $admin): TournamentDetailResponse
    {
        if (!$actor->isMaster()) {
            throw new TournamentAccessException();
        }
        $tournament = $this->context->requireTournament($tournamentId);
        $member = $this->requireMemberUser($tournament, $userId);
        if ($member->isAdmin() && !$admin && $this->members->countAdmins($tournament) <= 1) {
            throw new TournamentRuleException('last_admin', 'The tournament must keep at least one administrator.');
        }
        $member->setAdmin($admin);
        $this->entityManager->flush();

        return $this->views->detail($this->viewer($actor, $tournament), $tournament);
    }

    public function removeMember(User $actor, int $tournamentId, int $userId): TournamentDetailResponse
    {
        $tournament = $this->context->requireAdmin($actor, $tournamentId);
        $member = $this->requireMemberUser($tournament, $userId);
        if ($member->isAdmin() && !$actor->isMaster()) {
            throw new TournamentAccessException();
        }
        if ($member->isAdmin() && $this->members->countAdmins($tournament) <= 1) {
            throw new TournamentRuleException('last_admin', 'The tournament must keep at least one administrator.');
        }
        $player = $this->players->findOneByUser($member->getUser());
        if ($player instanceof Player && $this->teamPlayers->findOneByTournamentAndPlayer($tournament, $player) !== null) {
            throw new TournamentRuleException('player_still_in_team', 'This member plays in a team and stays in the tournament.', 409);
        }
        $this->entityManager->remove($member);
        $this->entityManager->flush();

        return $this->views->detail($actor, $tournament);
    }

    public function createTeam(User $actor, int $tournamentId, CreateTournamentTeamRequest $input): TournamentDetailResponse
    {
        $tournament = $this->context->requireAdmin($actor, $tournamentId);
        $playerIds = $this->uniqueIds($input->playerIds);
        $this->assertTeamSize($tournament, count($playerIds));
        $team = new TournamentTeam($tournament, trim($input->name));
        $this->entityManager->persist($team);
        foreach ($playerIds as $playerId) {
            $this->entityManager->persist(new TournamentTeamPlayer($team, $this->claimPlayer($tournament, $playerId)));
        }
        $this->entityManager->flush();

        return $this->views->detail($actor, $tournament);
    }

    public function renameTeam(User $actor, int $tournamentId, int $teamId, RenameTournamentTeamRequest $input): TournamentDetailResponse
    {
        $tournament = $this->context->requireAdmin($actor, $tournamentId);
        $this->context->requireTeam($tournament, $teamId)->rename(trim($input->name));
        $this->entityManager->flush();

        return $this->views->detail($actor, $tournament);
    }

    public function deleteTeam(User $actor, int $tournamentId, int $teamId): TournamentDetailResponse
    {
        $tournament = $this->context->requireAdmin($actor, $tournamentId);
        $team = $this->context->requireTeam($tournament, $teamId);
        if ($this->matches->countReferencingTeam($team) > 0) {
            throw new TournamentRuleException('team_has_matches', 'This team already has matches and cannot be deleted.');
        }
        $this->entityManager->remove($team);
        $this->entityManager->flush();

        return $this->views->detail($actor, $tournament);
    }

    public function addPlayer(User $actor, int $tournamentId, int $teamId, AddTournamentTeamPlayerRequest $input): TournamentDetailResponse
    {
        $tournament = $this->context->requireAdmin($actor, $tournamentId);
        $team = $this->context->requireTeam($tournament, $teamId);
        $count = $this->teamPlayers->countForTeam($team);
        if ($count >= $tournament->getMaxPlayersPerTeam()) {
            throw new TournamentRuleException('team_size', 'This team already has the maximum number of players.', 422);
        }
        $this->entityManager->persist(new TournamentTeamPlayer($team, $this->claimPlayer($tournament, $input->playerId)));
        $this->entityManager->flush();

        return $this->views->detail($actor, $tournament);
    }

    public function removePlayer(User $actor, int $tournamentId, int $teamId, int $playerId): TournamentDetailResponse
    {
        $tournament = $this->context->requireAdmin($actor, $tournamentId);
        $team = $this->context->requireTeam($tournament, $teamId);
        $player = $this->requirePlayer($playerId);
        $membership = $this->teamPlayers->findOneByTournamentAndPlayer($tournament, $player);
        if ($membership === null || $membership->getTeam()->getId() !== $team->getId()) {
            throw new TournamentRuleException('player_not_found', 'This player is not in the team.', 404);
        }
        if ($this->teamPlayers->countForTeam($team) <= $tournament->getMinPlayersPerTeam()) {
            throw new TournamentRuleException('team_size', 'The team would fall below the minimum number of players.', 422);
        }
        $this->entityManager->remove($membership);
        $this->entityManager->flush();

        return $this->views->detail($actor, $tournament);
    }

    private function claimPlayer(Tournament $tournament, int $playerId): Player
    {
        $player = $this->requirePlayer($playerId);
        if ($this->teamPlayers->findOneByTournamentAndPlayer($tournament, $player) !== null) {
            throw new TournamentRuleException('player_already_assigned', 'This player is already in a team of this tournament.', 409);
        }
        $this->ensureMembership($tournament, $player);

        return $player;
    }

    private function ensureMembership(Tournament $tournament, Player $player): void
    {
        $user = $player->getUser();
        if (!$user instanceof User) {
            return;
        }
        $this->membershipSync->ensureMember($tournament, $user);
    }

    private function requirePlayer(int $playerId): Player
    {
        $player = $this->players->find($playerId);
        if (!$player instanceof Player) {
            throw new TournamentRuleException('player_not_found', 'Player not found.', 422);
        }

        return $player;
    }

    private function requireMemberUser(Tournament $tournament, int $userId): TournamentMember
    {
        $user = $this->users->find($userId);
        if (!$user instanceof User) {
            throw new TournamentRuleException('user_not_found', 'User not found.', 422);
        }
        $member = $this->members->findOneByTournamentAndUser($tournament, $user);
        if ($member === null) {
            throw new TournamentRuleException('member_not_found', 'This user is not a member of the tournament.', 404);
        }

        return $member;
    }

    private function viewer(User $actor, Tournament $tournament): User
    {
        if ($this->members->findOneByTournamentAndUser($tournament, $actor) !== null) {
            return $actor;
        }
        $members = $this->members->findForTournament($tournament);

        return $members[0]->getUser();
    }

    /**
     * @param list<int> $ids
     * @return list<int>
     */
    private function uniqueIds(array $ids): array
    {
        $unique = [];
        foreach ($ids as $id) {
            if (isset($unique[(int) $id])) {
                throw new TournamentRuleException('player_already_assigned', 'The same player cannot be added twice.', 422);
            }
            $unique[(int) $id] = (int) $id;
        }

        return array_values($unique);
    }

    private function assertTeamSize(Tournament $tournament, int $count): void
    {
        if ($count < $tournament->getMinPlayersPerTeam() || $count > $tournament->getMaxPlayersPerTeam()) {
            throw new TournamentRuleException('team_size', 'The number of players must respect the tournament limits.', 422);
        }
    }
}
