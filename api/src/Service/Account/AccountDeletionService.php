<?php

declare(strict_types=1);

namespace App\Service\Account;

use App\Repository\PlayerRepository;
use App\Service\Auth\CurrentUserService;
use App\Service\Auth\InvalidTokenException;
use Doctrine\ORM\EntityManagerInterface;

final class AccountDeletionService
{
    public function __construct(
        private CurrentUserService $currentUserService,
        private PlayerRepository $players,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @throws InvalidTokenException
     */
    public function deleteAccount(string $token): void
    {
        $user = $this->currentUserService->getUserFromToken($token);
        $player = $this->players->findOneBy(['user' => $user]);
        if ($player !== null) {
            $player->setUser(null);
        }

        $this->em->remove($user);
        $this->em->flush();
    }
}
