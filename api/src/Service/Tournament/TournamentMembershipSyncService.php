<?php

declare(strict_types=1);

namespace App\Service\Tournament;

use App\Entity\Tournament;
use App\Entity\TournamentMember;
use App\Entity\User;
use App\Repository\TournamentMemberRepository;
use App\Repository\TournamentTeamPlayerRepository;
use Doctrine\ORM\EntityManagerInterface;

final class TournamentMembershipSyncService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TournamentMemberRepository $members,
        private TournamentTeamPlayerRepository $teamPlayers,
    ) {
    }

    public function syncForUser(User $user): void
    {
        foreach ($this->teamPlayers->findTournamentIdsForUser($user) as $tournamentId) {
            $tournament = $this->entityManager->find(Tournament::class, $tournamentId);
            if (!$tournament instanceof Tournament) {
                continue;
            }
            $this->ensureMember($tournament, $user);
        }
        $this->entityManager->flush();
    }

    public function ensureMember(Tournament $tournament, User $user): void
    {
        if ($this->members->findOneByTournamentAndUser($tournament, $user) !== null) {
            return;
        }
        if ($this->teamPlayers->findTeamIdsForUser($tournament, $user) === []) {
            return;
        }
        $this->entityManager->persist(new TournamentMember($tournament, $user, false));
    }

    public function userPlaysInTournament(User $user, Tournament $tournament): bool
    {
        return $this->teamPlayers->findTeamIdsForUser($tournament, $user) !== [];
    }
}
