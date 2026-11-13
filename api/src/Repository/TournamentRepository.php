<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tournament;
use App\Entity\TournamentMember;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tournament>
 */
final class TournamentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tournament::class);
    }

    public function lockById(int $id): void
    {
        $this->getEntityManager()->getConnection()->executeStatement(
            'SELECT id FROM tournaments WHERE id = ? FOR UPDATE',
            [$id],
        );
    }

    /**
     * @return list<Tournament>
     */
    public function findForUser(User $user): array
    {
        /** @var list<Tournament> $tournaments */
        $tournaments = $this->createQueryBuilder('t')
            ->innerJoin(TournamentMember::class, 'membership', 'WITH', 'membership.tournament = t')
            ->andWhere('membership.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $tournaments;
    }
}
