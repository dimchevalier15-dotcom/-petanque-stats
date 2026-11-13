<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tournament;
use App\Entity\TournamentDay;
use App\Enum\TournamentDayPhase;
use App\Enum\TournamentDayStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TournamentDay>
 */
final class TournamentDayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentDay::class);
    }

    public function findOneInTournament(Tournament $tournament, int $dayId): ?TournamentDay
    {
        return $this->findOneBy(['tournament' => $tournament, 'id' => $dayId]);
    }

    public function nextNumber(Tournament $tournament, TournamentDayPhase $phase): int
    {
        $max = $this->createQueryBuilder('day')
            ->select('MAX(day.number)')
            ->andWhere('day.tournament = :tournament')
            ->andWhere('day.phase = :phase')
            ->setParameter('tournament', $tournament)
            ->setParameter('phase', $phase)
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $max) + 1;
    }

    public function countForTournament(Tournament $tournament): int
    {
        return (int) $this->createQueryBuilder('day')
            ->select('COUNT(day.id)')
            ->andWhere('day.tournament = :tournament')
            ->setParameter('tournament', $tournament)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function hasOpenChampionshipDay(Tournament $tournament): bool
    {
        $count = (int) $this->createQueryBuilder('day')
            ->select('COUNT(day.id)')
            ->andWhere('day.tournament = :tournament')
            ->andWhere('day.phase = :phase')
            ->andWhere('day.status != :closed')
            ->setParameter('tournament', $tournament)
            ->setParameter('phase', TournamentDayPhase::CHAMPIONSHIP)
            ->setParameter('closed', TournamentDayStatus::CLOSED)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function countChampionshipDays(Tournament $tournament): int
    {
        return (int) $this->createQueryBuilder('day')
            ->select('COUNT(day.id)')
            ->andWhere('day.tournament = :tournament')
            ->andWhere('day.phase = :phase')
            ->setParameter('tournament', $tournament)
            ->setParameter('phase', TournamentDayPhase::CHAMPIONSHIP)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<TournamentDay>
     */
    public function findForTournament(Tournament $tournament): array
    {
        /** @var list<TournamentDay> $days */
        $days = $this->createQueryBuilder('day')
            ->andWhere('day.tournament = :tournament')
            ->setParameter('tournament', $tournament)
            ->orderBy('day.phase', 'ASC')
            ->addOrderBy('day.number', 'ASC')
            ->getQuery()
            ->getResult();

        return $days;
    }
}
