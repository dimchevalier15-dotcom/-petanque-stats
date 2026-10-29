<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\JumpShotSeries;
use App\Entity\JumpShotThrow;
use App\Enum\JumpShotThrowResult;
use App\Enum\ShootingDistance;
use App\ValueObject\DateRange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JumpShotThrow>
 */
class JumpShotThrowRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JumpShotThrow::class);
    }

    /**
     * @return list<JumpShotThrow>
     */
    public function findAllForSeriesOrdered(JumpShotSeries $series): array
    {
        /** @var list<JumpShotThrow> $rows */
        $rows = $this->createQueryBuilder('t')
            ->andWhere('t.series = :series')
            ->setParameter('series', $series)
            ->orderBy('t.position', 'ASC')
            ->getQuery()
            ->getResult();

        return $rows;
    }

    public function countForPlayerCompletedSessions(int $playerId, ?DateRange $range = null): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->join('t.series', 'ser')
            ->join('ser.session', 's')
            ->andWhere('s.player = :playerId')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->andWhere('s.won = true')
            ->setParameter('playerId', $playerId);

        $this->applySessionFinishedAtRange($qb, 's', $range);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return list<array{result:string,count:int}>
     */
    public function aggregateByResultForPlayer(int $playerId, ?DateRange $range = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t.result as result, COUNT(t.id) as count')
            ->join('t.series', 'ser')
            ->join('ser.session', 's')
            ->andWhere('s.player = :playerId')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->andWhere('s.won = true')
            ->setParameter('playerId', $playerId)
            ->groupBy('t.result')
            ->orderBy('t.result', 'ASC');

        $this->applySessionFinishedAtRange($qb, 's', $range);

        $rows = $qb->getQuery()->getArrayResult();

        return array_map(
            static fn (array $row): array => [
                'result' => $row['result'] instanceof JumpShotThrowResult ? $row['result']->value : (string) $row['result'],
                'count' => (int) $row['count'],
            ],
            $rows,
        );
    }

    /**
     * @return list<array{distance:int,throwCount:int,successCount:int}>
     */
    public function aggregateByDistanceForPlayer(int $playerId, ?DateRange $range = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('ser.distance as distance')
            ->addSelect('COUNT(t.id) as throwCount')
            ->addSelect('SUM(CASE WHEN t.result != :missed THEN 1 ELSE 0 END) as successCount')
            ->join('t.series', 'ser')
            ->join('ser.session', 's')
            ->andWhere('s.player = :playerId')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->andWhere('s.won = true')
            ->setParameter('playerId', $playerId)
            ->setParameter('missed', JumpShotThrowResult::MISSED)
            ->groupBy('ser.distance')
            ->orderBy('ser.distance', 'ASC');

        $this->applySessionFinishedAtRange($qb, 's', $range);

        $rows = $qb->getQuery()->getArrayResult();

        return array_map(
            static fn (array $row): array => [
                'distance' => $row['distance'] instanceof ShootingDistance
                    ? $row['distance']->value
                    : (int) $row['distance'],
                'throwCount' => (int) $row['throwCount'],
                'successCount' => (int) $row['successCount'],
            ],
            $rows,
        );
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
