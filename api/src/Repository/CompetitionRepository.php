<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Competition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Competition>
 */
final class CompetitionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Competition::class);
    }

    /**
     * @return list<Competition>
     */
    public function findAllOrdered(): array
    {
        /** @var list<Competition> $res */
        $res = $this->createQueryBuilder('c')
            ->orderBy('c.eventDate', 'DESC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $res;
    }
}
