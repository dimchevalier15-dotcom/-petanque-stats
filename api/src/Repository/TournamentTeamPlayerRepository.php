<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Player;
use App\Entity\Tournament;
use App\Entity\TournamentTeam;
use App\Entity\TournamentTeamPlayer;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TournamentTeamPlayer>
 */
final class TournamentTeamPlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentTeamPlayer::class);
    }

    public function findOneByTournamentAndPlayer(Tournament $tournament, Player $player): ?TournamentTeamPlayer
    {
        return $this->findOneBy(['tournament' => $tournament, 'player' => $player]);
    }

    public function countForTeam(TournamentTeam $team): int
    {
        return (int) $this->createQueryBuilder('membership')
            ->select('COUNT(membership.id)')
            ->andWhere('membership.team = :team')
            ->setParameter('team', $team)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<TournamentTeamPlayer>
     */
    public function findForTeam(TournamentTeam $team): array
    {
        /** @var list<TournamentTeamPlayer> $rows */
        $rows = $this->createQueryBuilder('membership')
            ->andWhere('membership.team = :team')
            ->setParameter('team', $team)
            ->orderBy('membership.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $rows;
    }

    /**
     * Team ids of the linked player. More than one means the highlight must stay off.
     *
     * @return list<int>
     */
    public function findTeamIdsForUser(Tournament $tournament, User $user): array
    {
        /** @var list<array{teamId: int}> $rows */
        $rows = $this->createQueryBuilder('membership')
            ->select('team.id AS teamId')
            ->innerJoin('membership.team', 'team')
            ->innerJoin('membership.player', 'player')
            ->andWhere('membership.tournament = :tournament')
            ->andWhere('player.user = :user')
            ->setParameter('tournament', $tournament)
            ->setParameter('user', $user)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): int => (int) $row['teamId'], $rows);
    }

    /**
     * @return list<int>
     */
    public function findTournamentIdsForUser(User $user): array
    {
        /** @var list<array{tournamentId: int}> $rows */
        $rows = $this->createQueryBuilder('membership')
            ->select('DISTINCT IDENTITY(membership.tournament) AS tournamentId')
            ->innerJoin('membership.player', 'player')
            ->andWhere('player.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): int => (int) $row['tournamentId'], $rows);
    }
}
