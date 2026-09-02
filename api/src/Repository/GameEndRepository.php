<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Game;
use App\Entity\GameEnd;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameEnd>
 */
final class GameEndRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameEnd::class);
    }

    public function countByGame(Game $game): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.game = :g')
            ->setParameter('g', $game)
            ->getQuery()->getSingleScalarResult();
    }

    /**
     * @return list<array{endIndex: int, canceled: bool}>
     */
    public function listIndexMetaByGame(Game $game): array
    {
        $rows = $this->createQueryBuilder('e')
            ->select('e.index AS endIndex, e.canceled AS canceled')
            ->where('e.game = :g')
            ->setParameter('g', $game)
            ->orderBy('e.index', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'endIndex' => (int) $row['endIndex'],
                'canceled' => (bool) $row['canceled'],
            ];
        }

        return $out;
    }

    /**
     * @param list<int> $gameIds
     *
     * @return array<int, list<GameEnd>>
     */
    public function findByGameIdsGrouped(array $gameIds): array
    {
        if ($gameIds === []) {
            return [];
        }

        /** @var list<GameEnd> $ends */
        $ends = $this->createQueryBuilder('e')
            ->addSelect('g')
            ->join('e.game', 'g')
            ->where('g.id IN (:gameIds)')
            ->setParameter('gameIds', $gameIds)
            ->orderBy('g.id', 'ASC')
            ->addOrderBy('e.index', 'ASC')
            ->getQuery()
            ->getResult();

        $grouped = [];
        foreach ($ends as $end) {
            $gameId = (int) $end->getGame()->getId();
            $grouped[$gameId][] = $end;
        }

        return $grouped;
    }

    public function deleteByGame(Game $game): void
    {
        $this->createQueryBuilder('e')
            ->delete()
            ->where('e.game = :game')
            ->setParameter('game', $game)
            ->getQuery()
            ->execute();
    }

    /**
     * @return array{A:int,B:int}
     */
    public function sumPointsByTeam(Game $game): array
    {
        $rows = $this->createQueryBuilder('e')
            ->select('e.winner as team, SUM(e.points) as pts')
            ->where('e.game = :g')
            ->andWhere('e.canceled = false')
            ->setParameter('g', $game)
            ->groupBy('e.winner')
            ->getQuery()->getArrayResult();
        $out = ['A' => 0, 'B' => 0];
        foreach ($rows as $r) {
            $team = (string) $r['team'];
            $out[$team] = (int) $r['pts'];
        }
        return $out;
    }
}
