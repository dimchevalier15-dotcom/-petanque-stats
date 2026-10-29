<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\JumpShotSeries;
use App\Entity\JumpShotSession;
use App\Entity\JumpShotThrow;
use App\Enum\JumpShotThrowResult;
use App\ValueObject\DateRange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JumpShotSeries>
 */
class JumpShotSeriesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JumpShotSeries::class);
    }

    public function countForSession(JumpShotSession $session): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.session = :session')
            ->setParameter('session', $session)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findLastForSession(JumpShotSession $session): ?JumpShotSeries
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.session = :session')
            ->setParameter('session', $session)
            ->orderBy('s.seriesNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<JumpShotSeries>
     */
    public function findAllForSessionOrdered(JumpShotSession $session): array
    {
        /** @var list<JumpShotSeries> $rows */
        $rows = $this->createQueryBuilder('s')
            ->andWhere('s.session = :session')
            ->setParameter('session', $session)
            ->orderBy('s.seriesNumber', 'ASC')
            ->getQuery()
            ->getResult();

        return $rows;
    }

    public function countForPlayerCompletedSessions(int $playerId, ?DateRange $range = null): int
    {
        $qb = $this->createQueryBuilder('ser')
            ->select('COUNT(ser.id)')
            ->join('ser.session', 's')
            ->andWhere('s.player = :playerId')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->andWhere('s.won = true')
            ->setParameter('playerId', $playerId);

        $this->applySessionFinishedAtRange($qb, 's', $range);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countSpecialForPlayerCompletedSessions(int $playerId, ?DateRange $range = null): int
    {
        $qb = $this->createQueryBuilder('ser')
            ->select('COUNT(ser.id)')
            ->join('ser.session', 's')
            ->andWhere('s.player = :playerId')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->andWhere('s.won = true')
            ->andWhere('ser.isSpecialSeries = true')
            ->setParameter('playerId', $playerId);

        $this->applySessionFinishedAtRange($qb, 's', $range);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function fewestSeriesAmongCompletedSessions(int $playerId, ?DateRange $range = null): ?int
    {
        $sessions = $this->getEntityManager()
            ->getRepository(JumpShotSession::class)
            ->findCompletedForPlayer($playerId, $range);

        if ($sessions === []) {
            return null;
        }

        $min = null;
        foreach ($sessions as $session) {
            $count = $this->countForSession($session);
            $min = $min === null ? $count : min($min, $count);
        }

        return $min;
    }

    private function applySessionFinishedAtRange(QueryBuilder $qb, string $sessionAlias, ?DateRange $range): void
    {
        if ($range === null) {
            return;
        }

        $qb->andWhere($sessionAlias.'.finishedAt >= :rangeFrom')
            ->andWhere($sessionAlias.'.finishedAt <= :rangeTo')
            ->setParameter('rangeFrom', $range->from)
            ->setParameter('rangeTo', $range->to);
    }
}
