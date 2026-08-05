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
}
