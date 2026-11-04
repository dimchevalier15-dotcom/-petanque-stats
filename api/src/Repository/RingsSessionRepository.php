<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\RingsSession;
use App\Enum\RingsMode;
use App\ValueObject\DateRange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    public function findInProgressForPlayer(int $playerId, RingsMode $mode): ?RingsSession
    {
        return $this->createQueryBuilder('s')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NULL')
            ->andWhere('s.mode = :mode')
            ->setParameter('pid', $playerId)
            ->setParameter('mode', $mode)
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array{0:int,1:list<RingsSession>}
     */
    public function findHistoryForPlayer(int $playerId, int $page, int $pageSize, RingsMode $mode): array
    {
        $page = max(1, $page);
        $pageSize = max(1, $pageSize);
        $offset = ($page - 1) * $pageSize;

        $total = (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->andWhere('s.mode = :mode')
            ->setParameter('pid', $playerId)
            ->setParameter('mode', $mode)
            ->getQuery()->getSingleScalarResult();

        /** @var list<RingsSession> $items */
        $items = $this->createQueryBuilder('s')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->andWhere('s.mode = :mode')
            ->setParameter('pid', $playerId)
            ->setParameter('mode', $mode)
            ->orderBy('s.playedAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($pageSize)
            ->getQuery()->getResult();

        return [$total, $items];
    }

    public function countCompletedForPlayer(int $playerId, ?RingsMode $mode = null, ?DateRange $range = null): int
    {
        $qb = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('pid', $playerId);

        $this->applyFilters($qb, 's', $mode, $range);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function bestTotalScoreForPlayer(int $playerId, ?RingsMode $mode = null, ?DateRange $range = null): ?int
    {
        $qb = $this->createQueryBuilder('s')
            ->select('MAX(s.totalScore)')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('pid', $playerId);

        $this->applyFilters($qb, 's', $mode, $range);

        $value = $qb->getQuery()->getSingleScalarResult();

        return $value !== null ? (int) $value : null;
    }

    public function averageTotalScoreForPlayer(int $playerId, ?RingsMode $mode = null, ?DateRange $range = null): ?float
    {
        $qb = $this->createQueryBuilder('s')
            ->select('AVG(s.totalScore)')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('pid', $playerId);

        $this->applyFilters($qb, 's', $mode, $range);

        $value = $qb->getQuery()->getSingleScalarResult();

        return $value !== null ? round((float) $value, 1) : null;
    }

    /**
     * @return list<int>
     */
    public function findCompletedSessionScoresForPlayer(int $playerId, ?RingsMode $mode = null, ?DateRange $range = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s.totalScore')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('pid', $playerId);

        $this->applyFilters($qb, 's', $mode, $range);

        /** @var list<int|string> $rows */
        $rows = $qb->getQuery()->getSingleColumnResult();

        return array_map(static fn ($score): int => (int) $score, $rows);
    }

    /**
     * @return list<array{id:int,playedAt:\DateTimeImmutable,totalScore:int}>
     */
    public function findEvolutionForPlayer(int $playerId, ?RingsMode $mode = null, ?DateRange $range = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('pid', $playerId)
            ->orderBy('s.playedAt', 'ASC');

        $this->applyFilters($qb, 's', $mode, $range);

        /** @var list<RingsSession> $sessions */
        $sessions = $qb->getQuery()->getResult();

        return array_map(
            static fn (RingsSession $s): array => [
                'id' => (int) $s->getId(),
                'playedAt' => $s->getPlayedAt(),
                'totalScore' => (int) $s->getTotalScore(),
            ],
            $sessions,
        );
    }

    private function applyFilters(QueryBuilder $qb, string $alias, ?RingsMode $mode, ?DateRange $range): void
    {
        if ($mode !== null) {
            $qb->andWhere($alias.'.mode = :mode')->setParameter('mode', $mode);
        }

        if ($range !== null) {
            $qb->andWhere($alias.'.playedAt >= :rangeFrom')
                ->andWhere($alias.'.playedAt <= :rangeTo')
                ->setParameter('rangeFrom', $range->from)
                ->setParameter('rangeTo', $range->to);
        }
    }
}
