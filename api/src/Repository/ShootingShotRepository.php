<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ShootingSession;
use App\Entity\ShootingShot;
use App\Enum\ShootingDistance;
use App\Enum\ShootingShotResult;
use App\Enum\ShootingWorkshop;
use App\ValueObject\DateRange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShootingShot>
 */
final class ShootingShotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShootingShot::class);
    }

    /**
     * @return list<ShootingShot>
     */
    public function findBySession(ShootingSession $session): array
    {
        /** @var list<ShootingShot> $items */
        $items = $this->createQueryBuilder('sh')
            ->where('sh.session = :session')
            ->setParameter('session', $session)
            ->orderBy('sh.workshop', 'ASC')
            ->addOrderBy('sh.distance', 'ASC')
            ->getQuery()->getResult();

        return $items;
    }

    /**
     * @return list<array{workshop:int,shotCount:int,sumScore:int}>
     */
    public function aggregateByWorkshopForPlayer(int $playerId, ?DateRange $range = null): array
    {
        $qb = $this->createQueryBuilder('sh')
            ->select('sh.workshop as workshop, COUNT(sh.id) as shotCount, SUM(sh.score) as sumScore')
            ->join('sh.session', 's')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('pid', $playerId)
            ->groupBy('sh.workshop')
            ->orderBy('sh.workshop', 'ASC');

        $this->applySessionDateRange($qb, 's', $range);

        $rows = $qb->getQuery()->getArrayResult();

        return array_map(
            static fn (array $r): array => [
                'workshop' => $r['workshop'] instanceof ShootingWorkshop ? $r['workshop']->value : (int) $r['workshop'],
                'shotCount' => (int) $r['shotCount'],
                'sumScore' => (int) $r['sumScore'],
            ],
            $rows,
        );
    }

    /**
     * @return list<array{distance:int,shotCount:int,sumScore:int}>
     */
    public function aggregateByDistanceForPlayer(int $playerId, ?DateRange $range = null): array
    {
        $qb = $this->createQueryBuilder('sh')
            ->select('sh.distance as distance, COUNT(sh.id) as shotCount, SUM(sh.score) as sumScore')
            ->join('sh.session', 's')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('pid', $playerId)
            ->groupBy('sh.distance')
            ->orderBy('sh.distance', 'ASC');

        $this->applySessionDateRange($qb, 's', $range);

        $rows = $qb->getQuery()->getArrayResult();

        return array_map(
            static fn (array $r): array => [
                'distance' => $r['distance'] instanceof ShootingDistance ? $r['distance']->value : (int) $r['distance'],
                'shotCount' => (int) $r['shotCount'],
                'sumScore' => (int) $r['sumScore'],
            ],
            $rows,
        );
    }

    /**
     * @return list<array{result:string,count:int}>
     */
    public function aggregateByResultForPlayer(int $playerId, ?DateRange $range = null): array
    {
        $qb = $this->createQueryBuilder('sh')
            ->select('sh.result as result, COUNT(sh.id) as count')
            ->join('sh.session', 's')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('pid', $playerId)
            ->groupBy('sh.result');

        $this->applySessionDateRange($qb, 's', $range);

        $rows = $qb->getQuery()->getArrayResult();

        return array_map(
            static fn (array $r): array => [
                'result' => $r['result'] instanceof ShootingShotResult ? $r['result']->value : (string) $r['result'],
                'count' => (int) $r['count'],
            ],
            $rows,
        );
    }

    /**
     * @return list<array{workshop:int,distance:int,shotCount:int,sumScore:int}>
     */
    public function aggregateByWorkshopAndDistanceForPlayer(int $playerId, ?DateRange $range = null): array
    {
        $qb = $this->createQueryBuilder('sh')
            ->select('sh.workshop as workshop, sh.distance as distance, COUNT(sh.id) as shotCount, SUM(sh.score) as sumScore')
            ->join('sh.session', 's')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('pid', $playerId)
            ->groupBy('sh.workshop, sh.distance')
            ->orderBy('sh.workshop', 'ASC')
            ->addOrderBy('sh.distance', 'ASC');

        $this->applySessionDateRange($qb, 's', $range);

        $rows = $qb->getQuery()->getArrayResult();

        return array_map(
            static fn (array $r): array => [
                'workshop' => $r['workshop'] instanceof ShootingWorkshop ? $r['workshop']->value : (int) $r['workshop'],
                'distance' => $r['distance'] instanceof ShootingDistance ? $r['distance']->value : (int) $r['distance'],
                'shotCount' => (int) $r['shotCount'],
                'sumScore' => (int) $r['sumScore'],
            ],
            $rows,
        );
    }

    public function countShotsForPlayer(int $playerId, ?DateRange $range = null): int
    {
        $qb = $this->createQueryBuilder('sh')
            ->select('COUNT(sh.id)')
            ->join('sh.session', 's')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('pid', $playerId);

        $this->applySessionDateRange($qb, 's', $range);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function applySessionDateRange(QueryBuilder $qb, string $sessionAlias, ?DateRange $range): void
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
