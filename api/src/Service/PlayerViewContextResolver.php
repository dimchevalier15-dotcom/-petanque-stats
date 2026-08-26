<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Player;
use App\Repository\PlayerRepository;
use App\Service\Auth\CurrentUserService;
use App\Service\Auth\InvalidTokenException;

final class PlayerViewContextResolver
{
    public function __construct(
        private CurrentUserService $currentUser,
        private PlayerRepository $players,
    ) {
    }

    /**
     * @throws InvalidTokenException
     */
    public function resolve(string $token, ?int $impersonatePlayerId = null): PlayerViewContext
    {
        $me = $this->currentUser->meFromToken($token);

        if ($impersonatePlayerId !== null) {
            $player = $this->players->find($impersonatePlayerId);
            if ($player === null) {
                return new PlayerViewContext(null, null, null);
            }

            return new PlayerViewContext(
                playerId: $impersonatePlayerId,
                historyUserId: $player->getUser()?->getId(),
                displayName: $this->displayNameForPlayer($player),
            );
        }

        return new PlayerViewContext(
            playerId: $me->playerId,
            historyUserId: $me->id,
            displayName: $this->buildDisplayName($me->nickname, $me->firstName, $me->lastName),
        );
    }

    private function displayNameForPlayer(Player $player): ?string
    {
        return $this->buildDisplayName($player->getNickname(), $player->getFirstName(), $player->getLastName());
    }

    private function buildDisplayName(?string $nickname, ?string $firstName, ?string $lastName): ?string
    {
        $full = trim(((string) $firstName).' '.((string) $lastName));
        if ($nickname !== null && $nickname !== '') {
            return $full !== '' ? $nickname.' ('.$full.')' : $nickname;
        }

        return $full !== '' ? $full : null;
    }
}
