<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\JumpShotSession;
use App\Entity\Player;
use App\ValueObject\DateRange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JumpShotSession>
 */
class JumpShotSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JumpShotSession::class);
    }

    public function findInProgressForPlayer(int $playerId): ?JumpShotSession
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.player = :playerId')
            ->andWhere('s.finishedAt IS NULL')
            ->setParameter('playerId', $playerId)
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByIdForPlayer(int $sessionId, Player $player): ?JumpShotSession
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.id = :id')
            ->andWhere('s.player = :player')
            ->setParameter('id', $sessionId)
            ->setParameter('player', $player)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array{0:int,1:list<JumpShotSession>}
     */
    public function findHistoryForPlayer(int $playerId, int $page, int $pageSize): array
    {
        $page = max(1, $page);
        $pageSize = max(1, $pageSize);
        $offset = ($page - 1) * $pageSize;

        $total = (int) $this->completedQueryBuilder($playerId)
            ->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult();

        /** @var list<JumpShotSession> $items */
        $items = $this->completedQueryBuilder($playerId)
            ->orderBy('s.finishedAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($pageSize)
            ->getQuery()
            ->getResult();

        return [$total, $items];
    }

    public function countCompletedForPlayer(int $playerId, ?DateRange $range = null): int
    {
        $qb = $this->completedQueryBuilder($playerId)->select('COUNT(s.id)');
        $this->applyFinishedAtRange($qb, $range);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return list<JumpShotSession>
     */
    public function findCompletedForPlayer(int $playerId, ?DateRange $range = null): array
    {
        $qb = $this->completedQueryBuilder($playerId)->orderBy('s.finishedAt', 'ASC');
        $this->applyFinishedAtRange($qb, $range);

        /** @var list<JumpShotSession> $items */
        $items = $qb->getQuery()->getResult();

        return $items;
    }

    private function completedQueryBuilder(int $playerId): QueryBuilder
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.player = :playerId')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->andWhere('s.won = true')
            ->setParameter('playerId', $playerId);
    }

    private function applyFinishedAtRange(QueryBuilder $qb, ?DateRange $range): void
    {
        if ($range === null) {
            return;
        }

        $qb->andWhere('s.finishedAt >= :rangeFrom')
            ->andWhere('s.finishedAt <= :rangeTo')
            ->setParameter('rangeFrom', $range->from)
            ->setParameter('rangeTo', $range->to);
    }
}
