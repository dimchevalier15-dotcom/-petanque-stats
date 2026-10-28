<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SelectionGlobale;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SelectionGlobale>
 */
final class SelectionGlobaleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SelectionGlobale::class);
    }

    /**
     * @return list<SelectionGlobale>
     */
    public function findAllOrderedByName(): array
    {
        /** @var list<SelectionGlobale> $res */
        $res = $this->createQueryBuilder('s')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $res;
    }
}
