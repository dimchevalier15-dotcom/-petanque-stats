<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TrainingSession;
use App\Enum\TrainingType;
use App\ValueObject\DateRange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TrainingSession>
 */
final class TrainingSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrainingSession::class);
    }

    public function findInProgressForPlayer(int $playerId): ?TrainingSession
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
     * @return array{0:int,1:list<TrainingSession>}
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

        /** @var list<TrainingSession> $items */
        $items = $this->createQueryBuilder('s')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('pid', $playerId)
            ->orderBy('s.finishedAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($pageSize)
            ->getQuery()->getResult();

        return [$total, $items];
    }

    /**
     * @return list<array{id:int,finishedAt:\DateTimeImmutable,totalScore:int,plannedBalls:int,successfulBalls:int}>
     */
    public function findEvolutionForPlayer(int $playerId, ?TrainingType $type = null, ?DateRange $range = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('pid', $playerId)
            ->orderBy('s.finishedAt', 'ASC');

        if ($type !== null) {
            $qb->andWhere('s.type = :type')->setParameter('type', $type);
        }

        $this->applyFinishedAtRange($qb, 's', $range);

        /** @var list<TrainingSession> $sessions */
        $sessions = $qb->getQuery()->getResult();

        return array_map(
            static fn (TrainingSession $s): array => [
                'id' => (int) $s->getId(),
                'finishedAt' => $s->getFinishedAt(),
                'totalScore' => (int) $s->getTotalScore(),
                'plannedBalls' => $s->getPlannedBalls(),
            ],
            $sessions,
        );
    }

    public function countCompletedForPlayer(int $playerId, ?TrainingType $type = null, ?DateRange $range = null): int
    {
        $qb = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('pid', $playerId);

        if ($type !== null) {
            $qb->andWhere('s.type = :type')->setParameter('type', $type);
        }

        $this->applyFinishedAtRange($qb, 's', $range);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function bestTotalScoreForPlayer(int $playerId, ?TrainingType $type = null, ?DateRange $range = null): ?int
    {
        $qb = $this->createQueryBuilder('s')
            ->select('MAX(s.totalScore)')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('pid', $playerId);

        if ($type !== null) {
            $qb->andWhere('s.type = :type')->setParameter('type', $type);
        }

        $this->applyFinishedAtRange($qb, 's', $range);

        $value = $qb->getQuery()->getSingleScalarResult();

        return $value !== null ? (int) $value : null;
    }

    public function averageTotalScoreForPlayer(int $playerId, ?TrainingType $type = null, ?DateRange $range = null): ?float
    {
        $qb = $this->createQueryBuilder('s')
            ->select('AVG(s.totalScore)')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('pid', $playerId);

        if ($type !== null) {
            $qb->andWhere('s.type = :type')->setParameter('type', $type);
        }

        $this->applyFinishedAtRange($qb, 's', $range);

        $value = $qb->getQuery()->getSingleScalarResult();

        return $value !== null ? round((float) $value, 1) : null;
    }

    private function applyFinishedAtRange(\Doctrine\ORM\QueryBuilder $qb, string $alias, ?DateRange $range): void
    {
        if ($range === null) {
            return;
        }

        $qb->andWhere($alias.'.finishedAt >= :rangeFrom')
            ->andWhere($alias.'.finishedAt <= :rangeTo')
            ->setParameter('rangeFrom', $range->from)
            ->setParameter('rangeTo', $range->to);
    }
}
