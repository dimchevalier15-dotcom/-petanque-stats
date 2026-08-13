<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TrainingAttempt;
use App\Entity\TrainingSession;
use App\Enum\TrainingPointResult;
use App\Enum\TrainingTirResult;
use App\Enum\TrainingType;
use App\ValueObject\DateRange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TrainingAttempt>
 */
final class TrainingAttemptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrainingAttempt::class);
    }

    /**
     * @return list<TrainingAttempt>
     */
    public function findBySession(TrainingSession $session): array
    {
        /** @var list<TrainingAttempt> $attempts */
        $attempts = $this->createQueryBuilder('a')
            ->where('a.session = :session')
            ->setParameter('session', $session)
            ->orderBy('a.number', 'ASC')
            ->getQuery()
            ->getResult();

        return $attempts;
    }

    public function countForSession(TrainingSession $session): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.session = :session')
            ->setParameter('session', $session)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function sumScoreForSession(TrainingSession $session): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COALESCE(SUM(a.score), 0)')
            ->where('a.session = :session')
            ->setParameter('session', $session)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countSuccessfulForSession(TrainingSession $session): int
    {
        $type = $session->getType();

        if ($type === TrainingType::POINT) {
            return (int) $this->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->where('a.session = :session')
                ->andWhere('a.result IN (:results)')
                ->setParameter('session', $session)
                ->setParameter('results', TrainingPointResult::successfulValues())
                ->getQuery()
                ->getSingleScalarResult();
        }

        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.session = :session')
            ->andWhere('a.result IN (:results)')
            ->setParameter('session', $session)
            ->setParameter('results', TrainingTirResult::successfulValues())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countForPlayer(int $playerId, ?TrainingType $type = null, ?DateRange $range = null): int
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->innerJoin('a.session', 's')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('pid', $playerId);

        $this->applySessionFilters($qb, $type, $range);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countSuccessfulForPlayer(int $playerId, ?TrainingType $type = null, ?DateRange $range = null): int
    {
        if ($type === TrainingType::POINT) {
            $qb = $this->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->innerJoin('a.session', 's')
                ->where('s.player = :pid')
                ->andWhere('s.finishedAt IS NOT NULL')
                ->andWhere('a.result IN (:results)')
                ->setParameter('pid', $playerId)
                ->setParameter('results', TrainingPointResult::successfulValues());
        } elseif ($type === TrainingType::TIR) {
            $qb = $this->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->innerJoin('a.session', 's')
                ->where('s.player = :pid')
                ->andWhere('s.finishedAt IS NOT NULL')
                ->andWhere('a.result IN (:results)')
                ->setParameter('pid', $playerId)
                ->setParameter('results', TrainingTirResult::successfulValues());
        } else {
            $qb = $this->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->innerJoin('a.session', 's')
                ->where('s.player = :pid')
                ->andWhere('s.finishedAt IS NOT NULL')
                ->andWhere('a.result IN (:pointSuccess) OR a.result IN (:tirSuccess)')
                ->setParameter('pid', $playerId)
                ->setParameter('pointSuccess', TrainingPointResult::successfulValues())
                ->setParameter('tirSuccess', TrainingTirResult::successfulValues());
        }

        $this->applySessionFilters($qb, $type, $range);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

  /**
   * @return list<array{type:string,ballCount:int,successfulCount:int,sumScore:int}>
   */
    public function aggregateByTypeForPlayer(int $playerId, ?DateRange $range = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a.type AS type')
            ->addSelect('COUNT(a.id) AS ballCount')
            ->addSelect('SUM(a.score) AS sumScore')
            ->innerJoin('a.session', 's')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('pid', $playerId)
            ->groupBy('a.type');

        $this->applySessionFilters($qb, null, $range);

        /** @var list<array{type:TrainingType,ballCount:int,sumScore:string}> $rows */
        $rows = $qb->getQuery()->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $type = $row['type'];
            $typeValue = $type instanceof TrainingType ? $type->value : (string) $type;
            $successfulCount = $this->countSuccessfulForPlayer(
                $playerId,
                TrainingType::from($typeValue),
                $range,
            );
            $result[] = [
                'type' => $typeValue,
                'ballCount' => (int) $row['ballCount'],
                'successfulCount' => $successfulCount,
                'sumScore' => (int) $row['sumScore'],
            ];
        }

        return $result;
    }

    /**
     * @return list<array{distance:float,ballCount:int,successfulCount:int,sumScore:int}>
     */
    public function aggregateByDistanceForPlayer(int $playerId, ?TrainingType $type = null, ?DateRange $range = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a.distance AS distance')
            ->addSelect('COUNT(a.id) AS ballCount')
            ->addSelect('SUM(a.score) AS sumScore')
            ->innerJoin('a.session', 's')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('pid', $playerId)
            ->groupBy('a.distance')
            ->orderBy('a.distance', 'ASC');

        $this->applySessionFilters($qb, $type, $range);

        /** @var list<array{distance:float,ballCount:int,sumScore:string}> $rows */
        $rows = $qb->getQuery()->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $distance = (float) $row['distance'];
            $successfulQb = $this->createQueryBuilder('a2')
                ->select('COUNT(a2.id)')
                ->innerJoin('a2.session', 's2')
                ->where('s2.player = :pid')
                ->andWhere('s2.finishedAt IS NOT NULL')
                ->andWhere('a2.distance = :distance')
                ->setParameter('pid', $playerId)
                ->setParameter('distance', $distance);

            if ($type === TrainingType::POINT) {
                $successfulQb->andWhere('a2.result IN (:results)')
                    ->setParameter('results', TrainingPointResult::successfulValues());
            } elseif ($type === TrainingType::TIR) {
                $successfulQb->andWhere('a2.result IN (:results)')
                    ->setParameter('results', TrainingTirResult::successfulValues());
            } else {
                $successfulQb->andWhere('a2.result IN (:pointSuccess) OR a2.result IN (:tirSuccess)')
                    ->setParameter('pointSuccess', TrainingPointResult::successfulValues())
                    ->setParameter('tirSuccess', TrainingTirResult::successfulValues());
            }

            if ($type !== null) {
                $successfulQb->andWhere('a2.type = :type')->setParameter('type', $type);
            }

            if ($range !== null) {
                $successfulQb->andWhere('s2.finishedAt >= :rangeFrom')
                    ->andWhere('s2.finishedAt <= :rangeTo')
                    ->setParameter('rangeFrom', $range->from)
                    ->setParameter('rangeTo', $range->to);
            }

            $successfulCount = (int) $successfulQb->getQuery()->getSingleScalarResult();

            $result[] = [
                'distance' => $distance,
                'ballCount' => (int) $row['ballCount'],
                'successfulCount' => $successfulCount,
                'sumScore' => (int) $row['sumScore'],
            ];
        }

        return $result;
    }

    private function applySessionFilters(
        \Doctrine\ORM\QueryBuilder $qb,
        ?TrainingType $type,
        ?DateRange $range,
    ): void {
        if ($type !== null) {
            $qb->andWhere('s.type = :type')->setParameter('type', $type);
        }

        if ($range !== null) {
            $qb->andWhere('s.finishedAt >= :rangeFrom')
                ->andWhere('s.finishedAt <= :rangeTo')
                ->setParameter('rangeFrom', $range->from)
                ->setParameter('rangeTo', $range->to);
        }
    }
}
