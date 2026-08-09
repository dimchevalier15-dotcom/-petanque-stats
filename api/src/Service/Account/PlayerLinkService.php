<?php

declare(strict_types=1);

namespace App\Service\Account;

use App\Entity\Player;
use App\Entity\User;
use App\Repository\PlayerRepository;
use Doctrine\ORM\EntityManagerInterface;

final class PlayerLinkService
{
    public function __construct(
        private PlayerRepository $players,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @throws UserAlreadyHasPlayerException
     * @throws PlayerNotFoundException
     * @throws PlayerAlreadyLinkedException
     */
    public function linkToUser(User $user, int $playerId): Player
    {
        $userId = (int) $user->getId();
        if ($this->players->findOneByUserId($userId) !== null) {
            throw new UserAlreadyHasPlayerException();
        }

        $player = $this->players->findUnlinkedById($playerId);
        if ($player === null) {
            if ($this->players->find($playerId) === null) {
                throw new PlayerNotFoundException();
            }
            throw new PlayerAlreadyLinkedException();
        }

        $player->setUser($user);
        $this->em->flush();

        return $player;
    }
}

final class PlayerNotFoundException extends \RuntimeException {}

final class PlayerAlreadyLinkedException extends \RuntimeException {}

final class UserAlreadyHasPlayerException extends \RuntimeException {}
