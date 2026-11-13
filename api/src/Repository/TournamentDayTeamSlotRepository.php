<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TournamentDay;
use App\Entity\TournamentDayTeamSlot;
use App\Entity\TournamentMatch;
use App\Entity\TournamentTeam;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TournamentDayTeamSlot>
 */
final class TournamentDayTeamSlotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentDayTeamSlot::class);
    }

    public function teamPlaysOnDay(TournamentDay $day, TournamentTeam $team, ?int $exceptMatchId): bool
    {
        $qb = $this->createQueryBuilder('slot')
            ->select('COUNT(slot.id)')
            ->andWhere('slot.day = :day')
            ->andWhere('slot.team = :team')
            ->setParameter('day', $day)
            ->setParameter('team', $team);
        if ($exceptMatchId !== null) {
            $qb->andWhere('IDENTITY(slot.match) != :matchId')->setParameter('matchId', $exceptMatchId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function deleteForMatch(TournamentMatch $match): void
    {
        $this->createQueryBuilder('slot')
            ->delete()
            ->andWhere('slot.match = :match')
            ->setParameter('match', $match)
            ->getQuery()
            ->execute();
    }
}
