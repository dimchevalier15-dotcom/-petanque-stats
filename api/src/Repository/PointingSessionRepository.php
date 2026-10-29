<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PointingSession;
use App\Enum\ShootingContextNature;
use App\ValueObject\DateRange;
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

    /**
     * @return array{0:int,1:list<PointingSession>}
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

        /** @var list<PointingSession> $items */
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

    /**
     * @return list<array{id:int,playedAt:\DateTimeImmutable,totalScore:int}>
     */
    public function findEvolutionForPlayer(int $playerId, ?ShootingContextNature $contextNature = null, ?DateRange $range = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('pid', $playerId)
            ->orderBy('s.playedAt', 'ASC');

        $this->applyFilters($qb, 's', $contextNature, $range);

        /** @var list<PointingSession> $sessions */
        $sessions = $qb->getQuery()->getResult();

        return array_map(
            static fn (PointingSession $s): array => [
                'id' => (int) $s->getId(),
                'playedAt' => $s->getPlayedAt(),
                'totalScore' => (int) $s->getTotalScore(),
            ],
            $sessions,
        );
    }

    public function countCompletedForPlayer(int $playerId, ?ShootingContextNature $contextNature = null, ?DateRange $range = null): int
    {
        $qb = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('pid', $playerId);

        $this->applyFilters($qb, 's', $contextNature, $range);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function bestTotalScoreForPlayer(int $playerId, ?ShootingContextNature $contextNature = null, ?DateRange $range = null): ?int
    {
        $qb = $this->createQueryBuilder('s')
            ->select('MAX(s.totalScore)')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('pid', $playerId);

        $this->applyFilters($qb, 's', $contextNature, $range);

        $value = $qb->getQuery()->getSingleScalarResult();

        return $value !== null ? (int) $value : null;
    }

    /**
     * @param list<int> $playerIds
     *
     * @return array<int, array{sessionsCount:int,averageSessionScore:float|null,bestSessionScore:int|null}>
     */
    public function summarizeCompletedForPlayers(array $playerIds, ?DateRange $range = null): array
    {
        if ($playerIds === []) {
            return [];
        }

        $qb = $this->createQueryBuilder('s')
            ->select('IDENTITY(s.player) AS playerId')
            ->addSelect('COUNT(s.id) AS sessionsCount')
            ->addSelect('AVG(s.totalScore) AS averageSessionScore')
            ->addSelect('MAX(s.totalScore) AS bestSessionScore')
            ->where('s.player IN (:playerIds)')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('playerIds', $playerIds)
            ->groupBy('s.player');

        if ($range !== null) {
            $qb->andWhere('s.playedAt >= :rangeFrom')
                ->andWhere('s.playedAt <= :rangeTo')
                ->setParameter('rangeFrom', $range->from)
                ->setParameter('rangeTo', $range->to);
        }

        /** @var list<array{playerId:string,sessionsCount:string,averageSessionScore:string|null,bestSessionScore:string|null}> $rows */
        $rows = $qb->getQuery()->getArrayResult();

        $out = [];
        foreach ($rows as $row) {
            $playerId = (int) $row['playerId'];
            $out[$playerId] = [
                'sessionsCount' => (int) $row['sessionsCount'],
                'averageSessionScore' => $row['averageSessionScore'] !== null
                    ? round((float) $row['averageSessionScore'], 1)
                    : null,
                'bestSessionScore' => $row['bestSessionScore'] !== null ? (int) $row['bestSessionScore'] : null,
            ];
        }

        return $out;
    }

    public function averageTotalScoreForPlayer(int $playerId, ?ShootingContextNature $contextNature = null, ?DateRange $range = null): ?float
    {
        $qb = $this->createQueryBuilder('s')
            ->select('AVG(s.totalScore)')
            ->where('s.player = :pid')
            ->andWhere('s.finishedAt IS NOT NULL')
            ->setParameter('pid', $playerId);

        $this->applyFilters($qb, 's', $contextNature, $range);

        $value = $qb->getQuery()->getSingleScalarResult();

        return $value !== null ? round((float) $value, 1) : null;
    }

    private function applyFilters(\Doctrine\ORM\QueryBuilder $qb, string $alias, ?ShootingContextNature $contextNature, ?DateRange $range): void
    {
        if ($contextNature !== null) {
            $qb->andWhere($alias.'.contextNature = :contextNature')
                ->setParameter('contextNature', $contextNature);
        }

        if ($range !== null) {
            $qb->andWhere($alias.'.playedAt >= :rangeFrom')
                ->andWhere($alias.'.playedAt <= :rangeTo')
                ->setParameter('rangeFrom', $range->from)
                ->setParameter('rangeTo', $range->to);
        }
    }
}
