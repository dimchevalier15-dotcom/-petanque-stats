<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Game;
use App\Entity\GameTracked;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameTracked>
 */
final class GameTrackedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameTracked::class);
    }

    /**
     * @param list<int> $gameIds
     * @return array<int, list<int>>
     */
    public function findPlayerIdsByGameIds(array $gameIds): array
    {
        if ($gameIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('t')
            ->select('IDENTITY(t.game) as gameId, IDENTITY(t.player) as pid')
            ->where('t.game IN (:gameIds)')
            ->setParameter('gameIds', $gameIds)
            ->getQuery()
            ->getArrayResult();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row['gameId']][] = (int) $row['pid'];
        }

        foreach ($grouped as &$playerIds) {
            sort($playerIds);
        }

        return $grouped;
    }

    /**
     * @return list<int>
     */
    public function findPlayerIdsByGame(Game $game): array
    {
        $rows = $this->createQueryBuilder('t')
            ->select('IDENTITY(t.player) as pid')
            ->where('t.game = :g')
            ->setParameter('g', $game)
            ->getQuery()->getSingleColumnResult();
        return array_map('intval', $rows);
    }

    public function deleteByGame(Game $game): void
    {
        $this->createQueryBuilder('t')
            ->delete()
            ->where('t.game = :g')
            ->setParameter('g', $game)
            ->getQuery()
            ->execute();
    }
}
