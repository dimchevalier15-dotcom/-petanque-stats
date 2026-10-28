<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SelectionGlobale;
use App\Entity\SelectionGlobaleCoach;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SelectionGlobaleCoach>
 */
final class SelectionGlobaleCoachRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SelectionGlobaleCoach::class);
    }

    public function userCoachesSelection(User $user, int $selectionGlobaleId): bool
    {
        $count = (int) $this->createQueryBuilder('sc')
            ->select('COUNT(sc.id)')
            ->where('IDENTITY(sc.user) = :userId')
            ->andWhere('IDENTITY(sc.selectionGlobale) = :selectionId')
            ->setParameter('userId', (int) $user->getId())
            ->setParameter('selectionId', $selectionGlobaleId)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * @return list<SelectionGlobale>
     */
    public function findSelectionsForUser(User $user): array
    {
        /** @var list<SelectionGlobaleCoach> $rows */
        $rows = $this->createQueryBuilder('sc')
            ->innerJoin('sc.selectionGlobale', 's')
            ->addSelect('s')
            ->where('IDENTITY(sc.user) = :userId')
            ->setParameter('userId', (int) $user->getId())
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();

        $out = [];
        foreach ($rows as $row) {
            $out[] = $row->getSelectionGlobale();
        }

        return $out;
    }

    /**
     * @return list<SelectionGlobaleCoach>
     */
    public function findBySelection(SelectionGlobale $selectionGlobale): array
    {
        /** @var list<SelectionGlobaleCoach> $res */
        $res = $this->createQueryBuilder('sc')
            ->innerJoin('sc.user', 'u')
            ->addSelect('u')
            ->where('sc.selectionGlobale = :selection')
            ->setParameter('selection', $selectionGlobale)
            ->orderBy('u.email', 'ASC')
            ->getQuery()
            ->getResult();

        return $res;
    }

    public function findOneBySelectionAndUser(SelectionGlobale $selectionGlobale, User $user): ?SelectionGlobaleCoach
    {
        return $this->findOneBy([
            'selectionGlobale' => $selectionGlobale,
            'user' => $user,
        ]);
    }
}
