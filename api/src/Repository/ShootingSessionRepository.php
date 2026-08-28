<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ShootingSession;
use App\Enum\ShootingContextNature;
use App\ValueObject\DateRange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShootingSession>
 */
final class ShootingSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShootingSession::class);
    }

    public function findInProgressForPlayer(int $playerId): ?ShootingSession
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
     * Completed sessions for a player, most recent first.
     *
     * @return array{0:int,1:list<ShootingSession>}
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

        /** @var list<ShootingSession> $items */
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
     * Completed sessions for evolution charts, oldest first.
     *
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

        /** @var list<ShootingSession> $sessions */
        $sessions = $qb->getQuery()->getResult();

        return array_map(
            static fn (ShootingSession $s): array => [
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
