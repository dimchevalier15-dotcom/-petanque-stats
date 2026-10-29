<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PointingSession;
use App\Entity\PointingShot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PointingShot>
 */
final class PointingShotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PointingShot::class);
    }

    /**
     * @return list<PointingShot>
     */
    public function findBySession(PointingSession $session): array
    {
        /** @var list<PointingShot> $items */
        $items = $this->createQueryBuilder('sh')
            ->where('sh.session = :session')
            ->setParameter('session', $session)
            ->orderBy('sh.workshop', 'ASC')
            ->addOrderBy('sh.distance', 'ASC')
            ->getQuery()->getResult();

        return $items;
    }
}
