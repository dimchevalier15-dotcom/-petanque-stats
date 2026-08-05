<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Player>
 */
final class PlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Player::class);
    }

    /**
     * @return list<Player>
     */
    public function searchByQuery(string $q, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('p');
        if ($q !== '') {
            $qb->where($qb->expr()->orX(
                $qb->expr()->like('LOWER(p.firstName)', ':q'),
                $qb->expr()->like('LOWER(p.lastName)', ':q'),
                $qb->expr()->like('LOWER(p.nickname)', ':q'),
            ))->setParameter('q', '%'.mb_strtolower($q).'%');
        }
        $qb->setMaxResults($limit)->orderBy('p.firstName', 'ASC');
        /** @var list<Player> $res */
        $res = $qb->getQuery()->getResult();
        return $res;
    }

    /**
     * @param list<int> $ids
     * @return array<int, Player> Map id => Player
     */
    public function findMapByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        /** @var list<Player> $players */
        $players = $this->createQueryBuilder('p')
            ->where('p.id IN (:ids)')->setParameter('ids', $ids)
            ->getQuery()->getResult();
        $map = [];
        foreach ($players as $p) {
            $map[(int) $p->getId()] = $p;
        }
        return $map;
    }

    public function findOneByUserId(int $userId): ?Player
    {
        return $this->findOneBy(['user' => $userId]);
    }
}
