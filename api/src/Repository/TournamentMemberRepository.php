<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tournament;
use App\Entity\TournamentMember;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TournamentMember>
 */
final class TournamentMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentMember::class);
    }

    public function findOneByTournamentAndUser(Tournament $tournament, User $user): ?TournamentMember
    {
        return $this->findOneBy(['tournament' => $tournament, 'user' => $user]);
    }

    public function userIsAdminSomewhere(User $user): bool
    {
        $count = (int) $this->createQueryBuilder('membership')
            ->select('COUNT(membership.id)')
            ->andWhere('membership.user = :user')
            ->andWhere('membership.admin = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function countAdmins(Tournament $tournament): int
    {
        return (int) $this->createQueryBuilder('membership')
            ->select('COUNT(membership.id)')
            ->andWhere('membership.tournament = :tournament')
            ->andWhere('membership.admin = true')
            ->setParameter('tournament', $tournament)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<TournamentMember>
     */
    public function findForTournament(Tournament $tournament): array
    {
        /** @var list<TournamentMember> $memberships */
        $memberships = $this->createQueryBuilder('membership')
            ->andWhere('membership.tournament = :tournament')
            ->setParameter('tournament', $tournament)
            ->orderBy('membership.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $memberships;
    }
}
