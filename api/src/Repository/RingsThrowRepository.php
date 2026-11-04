<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\RingsSession;
use App\Entity\RingsThrow;
use App\Enum\RingsMode;
use App\Enum\RingsTarget;
use App\ValueObject\DateRange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RingsThrow>
 */
final class RingsThrowRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RingsThrow::class);
    }

    /**
     * @return list<RingsThrow>
     */
    public function findBySession(RingsSession $session): array
    {
        /** @var list<RingsThrow> $items */
        $items = $this->createQueryBuilder('t')
            ->where('t.session = :session')
            ->setParameter('session', $session)
            ->orderBy('t.position', 'ASC')
            ->getQuery()->getResult();

        return $items;
    }

    public function countForPlayerCompletedSessions(int $playerId, ?RingsMode $mode = null, ?DateRange $range = null): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->join('t.session', 's')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('pid', $playerId);

        $this->applySessionFilters($qb, 's', $mode, $range);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countSuccessesForPlayerCompletedSessions(int $playerId, ?RingsMode $mode = null, ?DateRange $range = null): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->join('t.session', 's')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->andWhere('t.success = true')
            ->setParameter('pid', $playerId);

        $this->applySessionFilters($qb, 's', $mode, $range);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return list<array{target:string,attemptCount:int,successCount:int,carreauCount:int,sumScore:int}>
     */
    public function aggregateByTargetForPlayer(int $playerId, ?RingsMode $mode = null, ?DateRange $range = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t.target as target')
            ->addSelect('COUNT(t.id) as attemptCount')
            ->addSelect('SUM(CASE WHEN t.success = true THEN 1 ELSE 0 END) as successCount')
            ->addSelect('SUM(CASE WHEN t.carreau = true THEN 1 ELSE 0 END) as carreauCount')
            ->addSelect('SUM(t.score) as sumScore')
            ->join('t.session', 's')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('pid', $playerId)
            ->groupBy('t.target');

        $this->applySessionFilters($qb, 's', $mode, $range);

        $rows = $qb->getQuery()->getArrayResult();

        return array_map(
            static fn (array $r): array => [
                'target' => $r['target'] instanceof RingsTarget ? $r['target']->value : (string) $r['target'],
                'attemptCount' => (int) $r['attemptCount'],
                'successCount' => (int) $r['successCount'],
                'carreauCount' => (int) $r['carreauCount'],
                'sumScore' => (int) $r['sumScore'],
            ],
            $rows,
        );
    }

    private function applySessionFilters(QueryBuilder $qb, string $sessionAlias, ?RingsMode $mode, ?DateRange $range): void
    {
        if ($mode !== null) {
            $qb->andWhere($sessionAlias.'.mode = :mode')->setParameter('mode', $mode);
        }

        if ($range !== null) {
            $qb->andWhere($sessionAlias.'.playedAt >= :rangeFrom')
                ->andWhere($sessionAlias.'.playedAt <= :rangeTo')
                ->setParameter('rangeFrom', $range->from)
                ->setParameter('rangeTo', $range->to);
        }
    }
}
