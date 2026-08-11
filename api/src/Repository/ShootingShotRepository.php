<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ShootingSession;
use App\Entity\ShootingShot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShootingShot>
 */
final class ShootingShotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShootingShot::class);
    }

    /**
     * @return list<ShootingShot>
     */
    public function findBySession(ShootingSession $session): array
    {
        /** @var list<ShootingShot> $items */
        $items = $this->createQueryBuilder('sh')
            ->where('sh.session = :session')
            ->setParameter('session', $session)
            ->orderBy('sh.workshop', 'ASC')
            ->addOrderBy('sh.distance', 'ASC')
            ->getQuery()->getResult();

        return $items;
    }
}
