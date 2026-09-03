<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\GameParticipant;
use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameParticipant>
 */
final class GameParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameParticipant::class);
    }

    /**
     * @return list<int>
     */
    public function findPlayerIdsByGameAndTeam(Game $game, string $team): array
    {
        $rows = $this->createQueryBuilder('gp')
            ->select('IDENTITY(gp.player) as pid')
            ->where('gp.game = :game')
            ->andWhere('gp.team = :team')
            ->setParameter('game', $game)
            ->setParameter('team', $team)
            ->orderBy('gp.position', 'ASC')
            ->getQuery()->getSingleColumnResult();
        return array_map('intval', $rows);
    }

    /**
     * @return list<int>
     */
    public function findAllPlayerIdsByGame(Game $game): array
    {
        $rows = $this->createQueryBuilder('gp')
            ->select('IDENTITY(gp.player) as pid')
            ->where('gp.game = :game')
            ->setParameter('game', $game)
            ->getQuery()->getSingleColumnResult();
        return array_map('intval', $rows);
    }

    /**
     * @param list<int> $gameIds
     * @return array<int, array<int, string>>
     */
    public function mapPlayerTeamByGameIds(array $gameIds): array
    {
        if ($gameIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('gp')
            ->select('IDENTITY(gp.game) as gameId, IDENTITY(gp.player) as pid, gp.team as team')
            ->where('gp.game IN (:gameIds)')
            ->setParameter('gameIds', $gameIds)
            ->getQuery()
            ->getArrayResult();

        $maps = [];
        foreach ($rows as $row) {
            $maps[(int) $row['gameId']][(int) $row['pid']] = (string) $row['team'];
        }

        return $maps;
    }

    /**
     * @param list<int> $gameIds
     *
     * @return array<int, ?bool> Map gameId => hasValidatedMatch
     */
    public function mapValidationStatusByGameIds(int $playerId, array $gameIds): array
    {
        if ($gameIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('gp')
            ->select('IDENTITY(gp.game) as gameId, gp.hasValidatedMatch as validated')
            ->where('gp.game IN (:gameIds)')
            ->andWhere('gp.player = :playerId')
            ->setParameter('gameIds', $gameIds)
            ->setParameter('playerId', $playerId)
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['gameId']] = $row['validated'];
        }

        return $map;
    }

    /**
     * @return array<int, string> Map playerId => team ('A'|'B')
     */
    public function mapPlayerTeamByGame(Game $game): array
    {
        $rows = $this->createQueryBuilder('gp')
            ->select('IDENTITY(gp.player) as pid, gp.team as team')
            ->where('gp.game = :game')
            ->setParameter('game', $game)
            ->getQuery()->getArrayResult();
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['pid']] = (string) $r['team'];
        }
        return $map;
    }

    /**
     * @return array<int, string> Map playerId => default_shot_type ('point'|'tir')
     */
    public function mapDefaultShotTypeByGame(Game $game): array
    {
        $rows = $this->createQueryBuilder('gp')
            ->select('IDENTITY(gp.player) as pid, gp.defaultShotType as st')
            ->where('gp.game = :game')
            ->setParameter('game', $game)
            ->getQuery()->getArrayResult();
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['pid']] = (string) $r['st'];
        }
        return $map;
    }

    public function findByGameAndPlayer(Game $game, int $playerId): ?GameParticipant
    {
        return $this->createQueryBuilder('gp')
            ->where('gp.game = :game')
            ->andWhere('IDENTITY(gp.player) = :playerId')
            ->setParameter('game', $game)
            ->setParameter('playerId', $playerId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countPendingValidationForPlayer(int $playerId): int
    {
        return (int) $this->createQueryBuilder('gp')
            ->select('COUNT(gp.id)')
            ->join('gp.game', 'g')
            ->join('App\\Entity\\GameEnd', 'e', 'WITH', 'e.game = g')
            ->where('gp.player = :pid')
            ->andWhere('gp.hasValidatedMatch IS NULL')
            ->setParameter('pid', $playerId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<GameParticipant>
     */
    public function findPendingValidationForPlayer(int $playerId): array
    {
        /** @var list<GameParticipant> $items */
        $items = $this->createQueryBuilder('gp')
            ->addSelect('g')
            ->join('gp.game', 'g')
            ->join('App\\Entity\\GameEnd', 'e', 'WITH', 'e.game = g')
            ->where('gp.player = :pid')
            ->andWhere('gp.hasValidatedMatch IS NULL')
            ->setParameter('pid', $playerId)
            ->groupBy('gp.id')
            ->orderBy('g.playedAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $items;
    }

    /**
     * @return list<array{team: string, position: int, firstName: string, lastName: string, nickname: string}>
     */
    public function listParticipantsByGame(Game $game): array
    {
        return $this->createQueryBuilder('gp')
            ->select('gp.team as team, gp.position as position, p.firstName as firstName, p.lastName as lastName, p.nickname as nickname')
            ->join('gp.player', 'p')
            ->where('gp.game = :game')
            ->setParameter('game', $game)
            ->orderBy('gp.team', 'ASC')
            ->addOrderBy('gp.position', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
}
