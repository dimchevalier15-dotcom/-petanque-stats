<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Request\CreatePlayerRequest;
use App\Dto\Request\SearchPlayersQuery;
use App\Dto\Response\CreatePlayerResponse;
use App\Dto\Response\PlayerItem;
use App\Entity\Player;
use App\Repository\PlayerRepository;
use Doctrine\ORM\EntityManagerInterface;

final class PlayerService
{
    public function __construct(
        private PlayerRepository $players,
        private EntityManagerInterface $em,
    ) {}

    public function create(CreatePlayerRequest $req): CreatePlayerResponse
    {
        $player = new Player(
            firstName: $req->firstName,
            lastName: $req->lastName,
            nickname: $req->nickname !== null && $req->nickname !== '' ? $req->nickname : $req->firstName,
        );
        $player->setUser(null);

        $this->em->persist($player);
        $this->em->flush();

        return new CreatePlayerResponse(
            id: (int) $player->getId(),
            firstName: $player->getFirstName(),
            lastName: $player->getLastName(),
            nickname: $player->getNickname(),
        );
    }

    /**
     * @return list<PlayerItem>
     */
    public function search(SearchPlayersQuery $query): array
    {
        $q = trim((string) ($query->q ?? ''));
        $list = ($query->unlinkedOnly ?? false)
            ? $this->players->searchUnlinkedByQuery($q, 20)
            : $this->players->searchByQuery($q, 20);
        $out = [];
        foreach ($list as $p) {
            $out[] = new PlayerItem(
                id: (int) $p->getId(),
                firstName: $p->getFirstName(),
                lastName: $p->getLastName(),
                nickname: $p->getNickname(),
            );
        }
        return $out;
    }

    public function getOne(int $id): ?PlayerItem
    {
        $p = $this->players->find($id);
        if (!$p) {
            return null;
        }
        return new PlayerItem(
            id: (int) $p->getId(),
            firstName: $p->getFirstName(),
            lastName: $p->getLastName(),
            nickname: $p->getNickname(),
        );
    }
}
