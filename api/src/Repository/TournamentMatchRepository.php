<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tournament;
use App\Entity\TournamentDay;
use App\Entity\TournamentMatch;
use App\Entity\TournamentTeam;
use App\Enum\TournamentMatchPhase;
use App\Enum\TournamentPlayoffRound;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TournamentMatch>
 */
final class TournamentMatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentMatch::class);
    }

    public function findOneInTournament(Tournament $tournament, int $matchId): ?TournamentMatch
    {
        return $this->findOneBy(['tournament' => $tournament, 'id' => $matchId]);
    }

    public function laneUsedOnDay(TournamentDay $day, int $laneNumber, ?int $exceptMatchId): bool
    {
        $qb = $this->createQueryBuilder('match')
            ->select('COUNT(match.id)')
            ->andWhere('match.day = :day')
            ->andWhere('match.laneNumber = :lane')
            ->setParameter('day', $day)
            ->setParameter('lane', $laneNumber);
        if ($exceptMatchId !== null) {
            $qb->andWhere('match.id != :exceptId')->setParameter('exceptId', $exceptMatchId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * @return list<TournamentMatch>
     */
    public function findForDay(TournamentDay $day): array
    {
        /** @var list<TournamentMatch> $matches */
        $matches = $this->createQueryBuilder('match')
            ->andWhere('match.day = :day')
            ->setParameter('day', $day)
            ->orderBy('match.laneNumber', 'ASC')
            ->addOrderBy('match.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $matches;
    }

    /**
     * @return list<TournamentMatch>
     */
    public function findScoredChampionshipMatches(Tournament $tournament): array
    {
        /** @var list<TournamentMatch> $matches */
        $matches = $this->createQueryBuilder('match')
            ->andWhere('match.tournament = :tournament')
            ->andWhere('match.phase = :phase')
            ->andWhere('match.fictional = false')
            ->andWhere('match.score1 IS NOT NULL')
            ->andWhere('match.score2 IS NOT NULL')
            ->setParameter('tournament', $tournament)
            ->setParameter('phase', TournamentMatchPhase::CHAMPIONSHIP)
            ->getQuery()
            ->getResult();

        return $matches;
    }

    public function findPlayoffMatch(Tournament $tournament, TournamentPlayoffRound $round, int $slot): ?TournamentMatch
    {
        return $this->findOneBy([
            'tournament' => $tournament,
            'phase' => TournamentMatchPhase::PLAYOFF,
            'playoffRound' => $round,
            'playoffSlot' => $slot,
        ]);
    }

    public function findFinal(Tournament $tournament): ?TournamentMatch
    {
        return $this->findPlayoffMatch($tournament, TournamentPlayoffRound::FINAL, 1);
    }

    public function hasPlayoffMatches(Tournament $tournament): bool
    {
        $count = (int) $this->createQueryBuilder('match')
            ->select('COUNT(match.id)')
            ->andWhere('match.tournament = :tournament')
            ->andWhere('match.phase = :phase')
            ->setParameter('tournament', $tournament)
            ->setParameter('phase', TournamentMatchPhase::PLAYOFF)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function countReferencingTeam(TournamentTeam $team): int
    {
        return (int) $this->createQueryBuilder('match')
            ->select('COUNT(match.id)')
            ->andWhere('match.team1 = :team OR match.team2 = :team')
            ->setParameter('team', $team)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findFedBy(TournamentMatch $source): ?TournamentMatch
    {
        /** @var TournamentMatch|null $match */
        $match = $this->createQueryBuilder('match')
            ->andWhere('match.homeSourceMatch = :source OR match.awaySourceMatch = :source')
            ->setParameter('source', $source)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $match;
    }
}
