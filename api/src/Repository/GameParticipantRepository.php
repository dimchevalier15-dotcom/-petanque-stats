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
}
