<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tournament;
use App\Entity\TournamentChampionshipPairing;
use App\Entity\TournamentMatch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TournamentChampionshipPairing>
 */
final class TournamentChampionshipPairingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentChampionshipPairing::class);
    }

    public function findOneByMatch(TournamentMatch $match): ?TournamentChampionshipPairing
    {
        return $this->findOneBy(['match' => $match]);
    }

    /**
     * @return array<string, true>
     */
    public function pairKeys(Tournament $tournament): array
    {
        /** @var list<array{low: int, high: int}> $rows */
        $rows = $this->createQueryBuilder('pairing')
            ->select('pairing.teamLowId AS low, pairing.teamHighId AS high')
            ->andWhere('pairing.tournament = :tournament')
            ->setParameter('tournament', $tournament)
            ->getQuery()
            ->getArrayResult();

        $keys = [];
        foreach ($rows as $row) {
            $keys[((int) $row['low']).'-'.((int) $row['high'])] = true;
        }

        return $keys;
    }

    public function exists(Tournament $tournament, int $lowId, int $highId, ?int $exceptMatchId): bool
    {
        $qb = $this->createQueryBuilder('pairing')
            ->select('COUNT(pairing.id)')
            ->andWhere('pairing.tournament = :tournament')
            ->andWhere('pairing.teamLowId = :low')
            ->andWhere('pairing.teamHighId = :high')
            ->setParameter('tournament', $tournament)
            ->setParameter('low', $lowId)
            ->setParameter('high', $highId);
        if ($exceptMatchId !== null) {
            $qb->andWhere('IDENTITY(pairing.match) != :matchId')->setParameter('matchId', $exceptMatchId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
