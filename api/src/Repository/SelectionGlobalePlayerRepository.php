<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Player;
use App\Entity\SelectionGlobale;
use App\Entity\SelectionGlobalePlayer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SelectionGlobalePlayer>
 */
final class SelectionGlobalePlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SelectionGlobalePlayer::class);
    }

    public function isPlayerInSelection(int $selectionGlobaleId, int $playerId): bool
    {
        $count = (int) $this->createQueryBuilder('sp')
            ->select('COUNT(sp.id)')
            ->where('IDENTITY(sp.selectionGlobale) = :selectionId')
            ->andWhere('IDENTITY(sp.player) = :playerId')
            ->setParameter('selectionId', $selectionGlobaleId)
            ->setParameter('playerId', $playerId)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * @return list<Player>
     */
    public function findPlayersBySelectionId(int $selectionGlobaleId): array
    {
        /** @var list<SelectionGlobalePlayer> $rows */
        $rows = $this->createQueryBuilder('sp')
            ->innerJoin('sp.player', 'p')
            ->addSelect('p')
            ->where('IDENTITY(sp.selectionGlobale) = :selectionId')
            ->setParameter('selectionId', $selectionGlobaleId)
            ->orderBy('p.lastName', 'ASC')
            ->addOrderBy('p.firstName', 'ASC')
            ->getQuery()
            ->getResult();

        $out = [];
        foreach ($rows as $row) {
            $out[] = $row->getPlayer();
        }

        return $out;
    }

    public function findOneBySelectionAndPlayer(SelectionGlobale $selectionGlobale, Player $player): ?SelectionGlobalePlayer
    {
        return $this->findOneBy([
            'selectionGlobale' => $selectionGlobale,
            'player' => $player,
        ]);
    }

    /**
     * @return list<SelectionGlobalePlayer>
     */
    public function findBySelection(SelectionGlobale $selectionGlobale): array
    {
        /** @var list<SelectionGlobalePlayer> $res */
        $res = $this->createQueryBuilder('sp')
            ->innerJoin('sp.player', 'p')
            ->addSelect('p')
            ->where('sp.selectionGlobale = :selection')
            ->setParameter('selection', $selectionGlobale)
            ->orderBy('p.lastName', 'ASC')
            ->addOrderBy('p.firstName', 'ASC')
            ->getQuery()
            ->getResult();

        return $res;
    }
}
