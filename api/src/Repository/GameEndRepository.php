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
