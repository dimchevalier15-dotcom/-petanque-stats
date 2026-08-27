<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\LiveMatch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LiveMatch>
 */
final class LiveMatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LiveMatch::class);
    }

    public function findOneByUuid(string $uuid): ?LiveMatch
    {
        return $this->findOneBy(['uuid' => $uuid]);
    }
}
