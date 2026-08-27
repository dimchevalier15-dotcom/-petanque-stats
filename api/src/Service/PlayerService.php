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
        private PlayerClubResolver $playerClubResolver,
        private PlayerItemMapper $playerItemMapper,
        private EntityManagerInterface $em,
    ) {
    }

    public function create(CreatePlayerRequest $req): CreatePlayerResponse
    {
        $player = new Player(
            firstName: $req->firstName,
            lastName: $req->lastName,
            nickname: $req->nickname !== null && $req->nickname !== '' ? $req->nickname : $req->firstName,
        );
        $player->setUser(null);
        $player->setClub($this->playerClubResolver->resolveOptional($req->clubId));

        $this->em->persist($player);
        $this->em->flush();

        $item = $this->playerItemMapper->map($player);

        return new CreatePlayerResponse(
            id: $item->id,
            firstName: $item->firstName,
            lastName: $item->lastName,
            nickname: $item->nickname,
            clubId: $item->clubId,
            clubName: $item->clubName,
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
            $out[] = $this->playerItemMapper->map($p);
        }

        return $out;
    }

    public function getOne(int $id): ?PlayerItem
    {
        $p = $this->players->find($id);
        if (!$p) {
            return null;
        }

        return $this->playerItemMapper->map($p);
    }
}
