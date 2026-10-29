<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\JumpShotSeries;
use App\Entity\JumpShotThrow;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JumpShotThrow>
 */
class JumpShotThrowRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JumpShotThrow::class);
    }

    /**
     * @return list<JumpShotThrow>
     */
    public function findAllForSeriesOrdered(JumpShotSeries $series): array
    {
        /** @var list<JumpShotThrow> $rows */
        $rows = $this->createQueryBuilder('t')
            ->andWhere('t.series = :series')
            ->setParameter('series', $series)
            ->orderBy('t.position', 'ASC')
            ->getQuery()
            ->getResult();

        return $rows;
    }
}
