<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Game;
use App\Enum\MatchNature;
use App\ValueObject\DateRange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 */
final class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    /**
     * Returns total count and paginated games for a given player id, ordered by most recent first.
     *
     * @return array{0:int,1:list<Game>}
     */
    public function findHistoryForPlayer(int $playerId, int $page, int $pageSize): array
    {
        $page = max(1, $page);
        $pageSize = max(1, $pageSize);
        $offset = ($page - 1) * $pageSize;

        // total
        $total = (int) $this->createQueryBuilder('g')
            ->select('COUNT(DISTINCT g.id)')
            ->join('App\\Entity\\GameParticipant', 'gp', 'WITH', 'gp.game = g')
            ->where('gp.player = :pid')
            ->setParameter('pid', $playerId)
            ->getQuery()->getSingleScalarResult();

        // items
        /** @var list<Game> $items */
        $items = $this->createQueryBuilder('g')
            ->join('App\\Entity\\GameParticipant', 'gp', 'WITH', 'gp.game = g')
            ->where('gp.player = :pid')
            ->setParameter('pid', $playerId)
            ->orderBy('g.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($pageSize)
            ->getQuery()->getResult();

        return [$total, $items];
    }

    /**
     * Completed games (at least one end) where the player participated, oldest first.
     *
     * @return list<Game>
     */
    public function findCompletedGamesForPlayer(int $playerId, ?MatchNature $nature = null, ?DateRange $range = null): array
    {
        $qb = $this->createQueryBuilder('g')
            ->join('App\\Entity\\GameParticipant', 'gp', 'WITH', 'gp.game = g')
            ->join('App\\Entity\\GameEnd', 'e', 'WITH', 'e.game = g')
            ->where('gp.player = :pid')
            ->setParameter('pid', $playerId)
            ->groupBy('g.id')
            ->orderBy('g.createdAt', 'ASC');

        $this->applyFilters($qb, $nature, $range);

        /** @var list<Game> $items */
        $items = $qb->getQuery()->getResult();

        return $items;
    }

    public function countCompletedGamesForPlayer(int $playerId, ?MatchNature $nature = null, ?DateRange $range = null): int
    {
        $qb = $this->createQueryBuilder('g')
            ->select('COUNT(DISTINCT g.id)')
            ->join('App\\Entity\\GameParticipant', 'gp', 'WITH', 'gp.game = g')
            ->join('App\\Entity\\GameEnd', 'e', 'WITH', 'e.game = g')
            ->where('gp.player = :pid')
            ->setParameter('pid', $playerId);

        $this->applyFilters($qb, $nature, $range);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function applyFilters(\Doctrine\ORM\QueryBuilder $qb, ?MatchNature $nature, ?DateRange $range): void
    {
        if ($nature !== null) {
            $qb->andWhere('g.nature = :nature')->setParameter('nature', $nature);
        }

        if ($range !== null) {
            $qb->andWhere('g.createdAt >= :rangeFrom')
                ->andWhere('g.createdAt <= :rangeTo')
                ->setParameter('rangeFrom', $range->from)
                ->setParameter('rangeTo', $range->to);
        }
    }
}
