<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Game;
use App\Entity\GameBall;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameBall>
 */
final class GameBallRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameBall::class);
    }

    /**
     * Returns aggregates per player for given game id.
     *
     * @return array<int, array{count:int,sum:int,p2:int,p1:int,p0:int,m1:int,m2:int}> Map playerId => stats
     */
    public function aggregateByGame(Game $game): array
    {
        // Sum and count by player
        $rows = $this->createQueryBuilder('b')
            ->select('IDENTITY(b.player) as pid, COUNT(b.id) as cnt, SUM(b.note) as s')
            ->join('b.end', 'e')
            ->where('e.game = :g')
            ->setParameter('g', $game)
            ->groupBy('pid')
            ->getQuery()->getArrayResult();
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['pid']] = [
                'count' => (int) $r['cnt'],
                'sum' => (int) $r['s'],
                'p2' => 0,
                'p1' => 0,
                'p0' => 0,
                'm1' => 0,
                'm2' => 0,
            ];
        }

        // Counts by note per player
        $noteRows = $this->createQueryBuilder('b')
            ->select('IDENTITY(b.player) as pid, b.note as n, COUNT(b.id) as c')
            ->join('b.end', 'e')
            ->where('e.game = :g')
            ->setParameter('g', $game)
            ->groupBy('pid, n')
            ->getQuery()->getArrayResult();
        foreach ($noteRows as $r) {
            $pid = (int) $r['pid'];
            $n = (int) $r['n'];
            $c = (int) $r['c'];
            if (!isset($map[$pid])) {
                $map[$pid] = [
                    'count' => 0,
                    'sum' => 0,
                    'p2' => 0,
                    'p1' => 0,
                    'p0' => 0,
                    'm1' => 0,
                    'm2' => 0,
                ];
            }
            switch ($n) {
                case 2: $map[$pid]['p2'] = $c; break;
                case 1: $map[$pid]['p1'] = $c; break;
                case 0: $map[$pid]['p0'] = $c; break;
                case -1: $map[$pid]['m1'] = $c; break;
                case -2: $map[$pid]['m2'] = $c; break;
            }
        }
        return $map;
    }

    /**
     * Aggregates per player split by shot type ('point' | 'tir').
     *
     * @return array<int, array<string, array{count:int,sum:int,p2:int,p1:int,p0:int,m1:int,m2:int}>>
     */
    public function aggregateByGamePerShot(Game $game): array
    {
        // Sum and count by player and shot type
        $rows = $this->createQueryBuilder('b')
            ->select('IDENTITY(b.player) as pid, b.shotType as st, COUNT(b.id) as cnt, SUM(b.note) as s')
            ->join('b.end', 'e')
            ->where('e.game = :g')
            ->setParameter('g', $game)
            ->groupBy('pid, st')
            ->getQuery()->getArrayResult();
        $map = [];
        foreach ($rows as $r) {
            $pid = (int) $r['pid'];
            $st = (string) $r['st'];
            if (!isset($map[$pid])) $map[$pid] = [];
            $map[$pid][$st] = [
                'count' => (int) $r['cnt'],
                'sum' => (int) $r['s'],
                'p2' => 0,
                'p1' => 0,
                'p0' => 0,
                'm1' => 0,
                'm2' => 0,
            ];
        }

        // Counts by note per player and shot type
        $noteRows = $this->createQueryBuilder('b')
            ->select('IDENTITY(b.player) as pid, b.shotType as st, b.note as n, COUNT(b.id) as c')
            ->join('b.end', 'e')
            ->where('e.game = :g')
            ->setParameter('g', $game)
            ->groupBy('pid, st, n')
            ->getQuery()->getArrayResult();
        foreach ($noteRows as $r) {
            $pid = (int) $r['pid'];
            $st = (string) $r['st'];
            $n = (int) $r['n'];
            $c = (int) $r['c'];
            if (!isset($map[$pid][$st])) {
                if (!isset($map[$pid])) $map[$pid] = [];
                $map[$pid][$st] = [
                    'count' => 0,
                    'sum' => 0,
                    'p2' => 0,
                    'p1' => 0,
                    'p0' => 0,
                    'm1' => 0,
                    'm2' => 0,
                ];
            }
            switch ($n) {
                case 2: $map[$pid][$st]['p2'] = $c; break;
                case 1: $map[$pid][$st]['p1'] = $c; break;
                case 0: $map[$pid][$st]['p0'] = $c; break;
                case -1: $map[$pid][$st]['m1'] = $c; break;
                case -2: $map[$pid][$st]['m2'] = $c; break;
            }
        }
        return $map;
    }
}
