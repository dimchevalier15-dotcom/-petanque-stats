<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\JumpShotSession;
use App\Entity\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JumpShotSession>
 */
class JumpShotSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JumpShotSession::class);
    }

    public function findInProgressForPlayer(int $playerId): ?JumpShotSession
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.player = :playerId')
            ->andWhere('s.finishedAt IS NULL')
            ->setParameter('playerId', $playerId)
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByIdForPlayer(int $sessionId, Player $player): ?JumpShotSession
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.id = :id')
            ->andWhere('s.player = :player')
            ->setParameter('id', $sessionId)
            ->setParameter('player', $player)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
