<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Response\PlayerItem;
use App\Entity\Player;

final class PlayerItemMapper
{
    public function map(Player $player): PlayerItem
    {
        $club = $player->getClub();

        return new PlayerItem(
            id: (int) $player->getId(),
            firstName: $player->getFirstName(),
            lastName: $player->getLastName(),
            nickname: $player->getNickname(),
            clubId: $club?->getId() !== null ? (int) $club->getId() : null,
            clubName: $club?->getName(),
        );
    }
}
