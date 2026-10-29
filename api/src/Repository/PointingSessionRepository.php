<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PointingSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PointingSession>
 */
final class PointingSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PointingSession::class);
    }

    public function findInProgressForPlayer(int $playerId): ?PointingSession
    {
        return $this->createQueryBuilder('s')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NULL')
            ->setParameter('pid', $playerId)
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
