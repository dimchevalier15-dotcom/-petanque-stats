<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Game;
use App\Entity\GameBall;
use App\Enum\DistanceBucket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    /**
     * Sum of ball notes per player in each end (including canceled ends).
     *
     * @return array<int, array<int, int>> endIndex => playerId => total
     */
    public function sumNotesByPlayerAndEnd(Game $game): array
    {
        $rows = $this->createQueryBuilder('b')
            ->select('e.index as endIndex, IDENTITY(b.player) as pid, SUM(b.note) as s')
            ->join('b.end', 'e')
            ->where('e.game = :g')
            ->setParameter('g', $game)
            ->groupBy('e.index, b.player')
            ->orderBy('e.index', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($rows as $r) {
            $endIndex = (int) $r['endIndex'];
            $pid = (int) $r['pid'];
            $map[$endIndex][$pid] = (int) $r['s'];
        }

        return $map;
    }

    /**
     * @return array{count:int,sum:int,p2:int,p1:int,p0:int,m1:int,m2:int}
     */
    public function aggregateByPlayer(int $playerId): array
    {
        return $this->aggregateByPlayerForGames($playerId, null);
    }

    /**
     * @param list<int>|null $gameIds When null, aggregates across all games.
     *
     * @return array{count:int,sum:int,p2:int,p1:int,p0:int,m1:int,m2:int}
     */
    public function aggregateByPlayerForGames(int $playerId, ?array $gameIds, ?DistanceBucket $distanceBucket = null): array
    {
        $empty = [
            'count' => 0,
            'sum' => 0,
            'p2' => 0,
            'p1' => 0,
            'p0' => 0,
            'm1' => 0,
            'm2' => 0,
        ];

        if ($gameIds !== null && $gameIds === []) {
            return $empty;
        }

        $qb = $this->createQueryBuilder('b')
            ->select('COUNT(b.id) as cnt, SUM(b.note) as s')
            ->join('b.end', 'e')
            ->where('b.player = :pid')
            ->setParameter('pid', $playerId);

        $this->applyGameIdsFilter($qb, $gameIds);
        $this->applyDistanceBucketFilter($qb, $distanceBucket);

        $row = $qb->getQuery()->getOneOrNullResult();
        $map = $empty;
        if ($row !== null) {
            $map['count'] = (int) ($row['cnt'] ?? 0);
            $map['sum'] = (int) ($row['s'] ?? 0);
        }

        $noteQb = $this->createQueryBuilder('b')
            ->select('b.note as n, COUNT(b.id) as c')
            ->join('b.end', 'e')
            ->where('b.player = :pid')
            ->setParameter('pid', $playerId)
            ->groupBy('n');

        $this->applyGameIdsFilter($noteQb, $gameIds);
        $this->applyDistanceBucketFilter($noteQb, $distanceBucket);

        foreach ($noteQb->getQuery()->getArrayResult() as $r) {
            $n = (int) $r['n'];
            $c = (int) $r['c'];
            switch ($n) {
                case 2: $map['p2'] = $c; break;
                case 1: $map['p1'] = $c; break;
                case 0: $map['p0'] = $c; break;
                case -1: $map['m1'] = $c; break;
                case -2: $map['m2'] = $c; break;
            }
        }

        return $map;
    }

    /**
     * @return array<string, array{count:int,sum:int,p2:int,p1:int,p0:int,m1:int,m2:int}>
     */
    public function aggregateByPlayerPerShot(int $playerId): array
    {
        return $this->aggregateByPlayerPerShotForGames($playerId, null);
    }

    /**
     * @param list<int>|null $gameIds
     *
     * @return array<string, array{count:int,sum:int,p2:int,p1:int,p0:int,m1:int,m2:int}>
     */
    public function aggregateByPlayerPerShotForGames(int $playerId, ?array $gameIds, ?DistanceBucket $distanceBucket = null): array
    {
        $map = [];

        if ($gameIds !== null && $gameIds === []) {
            return $map;
        }

        $qb = $this->createQueryBuilder('b')
            ->select('b.shotType as st, COUNT(b.id) as cnt, SUM(b.note) as s')
            ->join('b.end', 'e')
            ->where('b.player = :pid')
            ->setParameter('pid', $playerId)
            ->groupBy('st');

        $this->applyGameIdsFilter($qb, $gameIds);
        $this->applyDistanceBucketFilter($qb, $distanceBucket);

        foreach ($qb->getQuery()->getArrayResult() as $r) {
            $st = (string) $r['st'];
            $map[$st] = [
                'count' => (int) $r['cnt'],
                'sum' => (int) $r['s'],
                'p2' => 0,
                'p1' => 0,
                'p0' => 0,
                'm1' => 0,
                'm2' => 0,
            ];
        }

        $noteQb = $this->createQueryBuilder('b')
            ->select('b.shotType as st, b.note as n, COUNT(b.id) as c')
            ->join('b.end', 'e')
            ->where('b.player = :pid')
            ->setParameter('pid', $playerId)
            ->groupBy('st, n');

        $this->applyGameIdsFilter($noteQb, $gameIds);
        $this->applyDistanceBucketFilter($noteQb, $distanceBucket);

        foreach ($noteQb->getQuery()->getArrayResult() as $r) {
            $st = (string) $r['st'];
            $n = (int) $r['n'];
            $c = (int) $r['c'];
            if (!isset($map[$st])) {
                $map[$st] = [
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
                case 2: $map[$st]['p2'] = $c; break;
                case 1: $map[$st]['p1'] = $c; break;
                case 0: $map[$st]['p0'] = $c; break;
                case -1: $map[$st]['m1'] = $c; break;
                case -2: $map[$st]['m2'] = $c; break;
            }
        }

        return $map;
    }

    /**
     * @param list<int>|null $gameIds
     *
     * @return array<int, array{count:int,sum:int,p2:int,p1:int,p0:int,m1:int,m2:int}>
     */
    public function aggregateByPlayerPerGame(int $playerId, ?array $gameIds = null, ?DistanceBucket $distanceBucket = null): array
    {
        if ($gameIds !== null && $gameIds === []) {
            return [];
        }

        $map = [];

        $rowsQb = $this->createQueryBuilder('b')
            ->select('IDENTITY(e.game) as gid, COUNT(b.id) as cnt, SUM(b.note) as s')
            ->join('b.end', 'e')
            ->where('b.player = :pid')
            ->setParameter('pid', $playerId)
            ->groupBy('gid');

        $this->applyGameIdsFilter($rowsQb, $gameIds);
        $this->applyDistanceBucketFilter($rowsQb, $distanceBucket);

        $rows = $rowsQb->getQuery()->getArrayResult();

        foreach ($rows as $r) {
            $gid = (int) $r['gid'];
            $map[$gid] = [
                'count' => (int) $r['cnt'],
                'sum' => (int) $r['s'],
                'p2' => 0,
                'p1' => 0,
                'p0' => 0,
                'm1' => 0,
                'm2' => 0,
            ];
        }

        $noteQb = $this->createQueryBuilder('b')
            ->select('IDENTITY(e.game) as gid, b.note as n, COUNT(b.id) as c')
            ->join('b.end', 'e')
            ->where('b.player = :pid')
            ->setParameter('pid', $playerId)
            ->groupBy('gid, n');

        $this->applyGameIdsFilter($noteQb, $gameIds);
        $this->applyDistanceBucketFilter($noteQb, $distanceBucket);

        $noteRows = $noteQb->getQuery()->getArrayResult();

        foreach ($noteRows as $r) {
            $gid = (int) $r['gid'];
            $n = (int) $r['n'];
            $c = (int) $r['c'];
            if (!isset($map[$gid])) {
                $map[$gid] = [
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
                case 2: $map[$gid]['p2'] = $c; break;
                case 1: $map[$gid]['p1'] = $c; break;
                case 0: $map[$gid]['p0'] = $c; break;
                case -1: $map[$gid]['m1'] = $c; break;
                case -2: $map[$gid]['m2'] = $c; break;
            }
        }

        return $map;
    }

    /**
     * @param list<int>|null $gameIds
     *
     * @return array<string, array{count:int,sum:int,p2:int,p1:int,p0:int,m1:int,m2:int}>
     */
    public function aggregateByPlayerPerDistanceBucketForGames(int $playerId, ?array $gameIds): array
    {
        if ($gameIds !== null && $gameIds === []) {
            return [];
        }

        $qb = $this->createQueryBuilder('b')
            ->select('b.distance as dist, b.note as n')
            ->join('b.end', 'e')
            ->where('b.player = :pid')
            ->andWhere('b.distance IS NOT NULL')
            ->setParameter('pid', $playerId);

        if ($gameIds !== null) {
            $qb->andWhere('e.game IN (:games)')->setParameter('games', $gameIds);
        }

        $map = [];
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            $bucket = DistanceBucket::fromDistance((float) $row['dist']);
            if ($bucket === null) {
                continue;
            }

            $key = $bucket->value;
            $n = (int) $row['n'];
            if (!isset($map[$key])) {
                $map[$key] = [
                    'count' => 0,
                    'sum' => 0,
                    'p2' => 0,
                    'p1' => 0,
                    'p0' => 0,
                    'm1' => 0,
                    'm2' => 0,
                ];
            }

            $map[$key]['count']++;
            $map[$key]['sum'] += $n;
            switch ($n) {
                case 2: $map[$key]['p2']++; break;
                case 1: $map[$key]['p1']++; break;
                case 0: $map[$key]['p0']++; break;
                case -1: $map[$key]['m1']++; break;
                case -2: $map[$key]['m2']++; break;
            }
        }

        return $map;
    }

    /**
     * @param list<int>|null $gameIds
     */
    private function applyGameIdsFilter(QueryBuilder $qb, ?array $gameIds): void
    {
        if ($gameIds === null) {
            return;
        }

        $qb->andWhere('e.game IN (:games)')->setParameter('games', $gameIds);
    }

    private function applyDistanceBucketFilter(QueryBuilder $qb, ?DistanceBucket $distanceBucket): void
    {
        if ($distanceBucket === null) {
            return;
        }

        $qb->andWhere('b.distance IS NOT NULL');

        match ($distanceBucket) {
            DistanceBucket::UNDER_6 => $qb->andWhere('b.distance < 6'),
            DistanceBucket::FROM_6_TO_7 => $qb->andWhere('b.distance >= 6 AND b.distance < 7'),
            DistanceBucket::FROM_7_TO_8 => $qb->andWhere('b.distance >= 7 AND b.distance < 8'),
            DistanceBucket::FROM_8_TO_9 => $qb->andWhere('b.distance >= 8 AND b.distance < 9'),
            DistanceBucket::FROM_9_TO_10 => $qb->andWhere('b.distance >= 9 AND b.distance < 10'),
            DistanceBucket::FROM_10_PLUS => $qb->andWhere('b.distance >= 10'),
        };
    }
}
