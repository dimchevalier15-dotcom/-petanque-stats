<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Game;
use App\Enum\GameType;
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
     * Returns total count and paginated completed games for an account:
     * matches the linked player participated in, or matches created by the user.
     *
     * @return array{0:int,1:list<Game>}
     */
    public function findHistoryForAccount(?int $userId, ?int $playerId, int $page, int $pageSize): array
    {
        $page = max(1, $page);
        $pageSize = max(1, $pageSize);
        $offset = ($page - 1) * $pageSize;

        $totalQb = $this->createHistoryForAccountQueryBuilder($userId, $playerId)
            ->select('COUNT(DISTINCT g.id)');
        $total = (int) $totalQb->getQuery()->getSingleScalarResult();

        /** @var list<Game> $items */
        $items = $this->createHistoryForAccountQueryBuilder($userId, $playerId)
            ->leftJoin('g.competition', 'c')->addSelect('c')
            ->groupBy('g.id')
            ->orderBy('g.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($pageSize)
            ->getQuery()
            ->getResult();

        return [$total, $items];
    }

    private function createHistoryForAccountQueryBuilder(?int $userId, ?int $playerId): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('g')
            ->join('App\\Entity\\GameEnd', 'e', 'WITH', 'e.game = g');

        $conditions = [];

        if ($userId !== null) {
            $qb->leftJoin('g.createdBy', 'creator');
            $conditions[] = 'creator.id = :userId';
            $qb->setParameter('userId', $userId);
        }

        if ($playerId !== null) {
            $qb->leftJoin('App\\Entity\\GameParticipant', 'gp', 'WITH', 'gp.game = g AND gp.player = :playerId')
                ->setParameter('playerId', $playerId);
            $conditions[] = 'gp.player IS NOT NULL';
        }

        if ($conditions === []) {
            $qb->where('1 = 0');
        } else {
            $qb->where($qb->expr()->orX(...$conditions));
        }

        return $qb;
    }

    /**
     * Completed games (at least one end) where the player participated, oldest first.
     *
     * @return list<Game>
     */
    public function findCompletedGamesForPlayer(
        int $playerId,
        ?MatchNature $nature = null,
        ?DateRange $range = null,
        ?GameType $type = null,
        ?int $competitionId = null,
    ): array {
        $qb = $this->createQueryBuilder('g')
            ->leftJoin('g.competition', 'c')->addSelect('c')
            ->join('App\\Entity\\GameParticipant', 'gp', 'WITH', 'gp.game = g')
            ->join('App\\Entity\\GameEnd', 'e', 'WITH', 'e.game = g')
            ->where('gp.player = :pid')
            ->setParameter('pid', $playerId)
            ->groupBy('g.id')
            ->orderBy('g.createdAt', 'ASC');

        $this->applyFilters($qb, $nature, $range, $type, $competitionId);

        /** @var list<Game> $items */
        $items = $qb->getQuery()->getResult();

        return $items;
    }

    public function countCompletedGamesForPlayer(
        int $playerId,
        ?MatchNature $nature = null,
        ?DateRange $range = null,
        ?GameType $type = null,
        ?int $competitionId = null,
    ): int {
        $qb = $this->createQueryBuilder('g')
            ->select('COUNT(DISTINCT g.id)')
            ->join('App\\Entity\\GameParticipant', 'gp', 'WITH', 'gp.game = g')
            ->join('App\\Entity\\GameEnd', 'e', 'WITH', 'e.game = g')
            ->where('gp.player = :pid')
            ->setParameter('pid', $playerId);

        $this->applyFilters($qb, $nature, $range, $type, $competitionId);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function applyFilters(
        \Doctrine\ORM\QueryBuilder $qb,
        ?MatchNature $nature,
        ?DateRange $range,
        ?GameType $type = null,
        ?int $competitionId = null,
    ): void {
        if ($nature !== null) {
            $qb->andWhere('g.nature = :nature')->setParameter('nature', $nature);
        }

        if ($type !== null) {
            $qb->andWhere('g.type = :type')->setParameter('type', $type);
        }

        if ($competitionId !== null) {
            $qb->andWhere('g.competition = :competitionId')->setParameter('competitionId', $competitionId);
        }

        if ($range !== null) {
            $qb->andWhere('g.createdAt >= :rangeFrom')
                ->andWhere('g.createdAt <= :rangeTo')
                ->setParameter('rangeFrom', $range->from)
                ->setParameter('rangeTo', $range->to);
        }
    }
}
