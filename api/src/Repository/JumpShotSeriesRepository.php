<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\JumpShotSeries;
use App\Entity\JumpShotSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JumpShotSeries>
 */
class JumpShotSeriesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JumpShotSeries::class);
    }

    public function countForSession(JumpShotSession $session): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.session = :session')
            ->setParameter('session', $session)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findLastForSession(JumpShotSession $session): ?JumpShotSeries
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.session = :session')
            ->setParameter('session', $session)
            ->orderBy('s.seriesNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<JumpShotSeries>
     */
    public function findAllForSessionOrdered(JumpShotSession $session): array
    {
        /** @var list<JumpShotSeries> $rows */
        $rows = $this->createQueryBuilder('s')
            ->andWhere('s.session = :session')
            ->setParameter('session', $session)
            ->orderBy('s.seriesNumber', 'ASC')
            ->getQuery()
            ->getResult();

        return $rows;
    }
}
