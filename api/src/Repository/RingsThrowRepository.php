<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\RingsSession;
use App\Entity\RingsThrow;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RingsThrow>
 */
final class RingsThrowRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RingsThrow::class);
    }

    /**
     * @return list<RingsThrow>
     */
    public function findBySession(RingsSession $session): array
    {
        /** @var list<RingsThrow> $items */
        $items = $this->createQueryBuilder('t')
            ->where('t.session = :session')
            ->setParameter('session', $session)
            ->orderBy('t.position', 'ASC')
            ->getQuery()->getResult();

        return $items;
    }
}
