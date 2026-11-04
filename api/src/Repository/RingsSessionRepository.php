<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\RingsSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RingsSession>
 */
final class RingsSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RingsSession::class);
    }

    public function findInProgressForPlayer(int $playerId): ?RingsSession
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

    /**
     * @return array{0:int,1:list<RingsSession>}
     */
    public function findHistoryForPlayer(int $playerId, int $page, int $pageSize): array
    {
        $page = max(1, $page);
        $pageSize = max(1, $pageSize);
        $offset = ($page - 1) * $pageSize;

        $total = (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('pid', $playerId)
            ->getQuery()->getSingleScalarResult();

        /** @var list<RingsSession> $items */
        $items = $this->createQueryBuilder('s')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('pid', $playerId)
            ->orderBy('s.playedAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($pageSize)
            ->getQuery()->getResult();

        return [$total, $items];
    }
}
