<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tournament;
use App\Entity\TournamentTeam;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TournamentTeam>
 */
final class TournamentTeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentTeam::class);
    }

    /**
     * @return list<TournamentTeam>
     */
    public function findForTournament(Tournament $tournament): array
    {
        /** @var list<TournamentTeam> $teams */
        $teams = $this->createQueryBuilder('team')
            ->andWhere('team.tournament = :tournament')
            ->setParameter('tournament', $tournament)
            ->orderBy('team.name', 'ASC')
            ->addOrderBy('team.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $teams;
    }

    public function findOneInTournament(Tournament $tournament, int $teamId): ?TournamentTeam
    {
        return $this->findOneBy(['tournament' => $tournament, 'id' => $teamId]);
    }
}
