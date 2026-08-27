<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Player;
use App\Entity\User;
use App\Repository\PlayerRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CoachAccessService
{
    public function __construct(private PlayerRepository $players)
    {
    }

    public function requireCoachClubId(User $user): int
    {
        $club = $user->getCoachForClub();
        if ($club === null) {
            throw new AccessDeniedHttpException('Coach access required.');
        }

        return (int) $club->getId();
    }

    public function assertCoachCanViewPlayer(User $user, int $playerId): Player
    {
        $clubId = $this->requireCoachClubId($user);
        $player = $this->players->find($playerId);
        if ($player === null) {
            throw new NotFoundHttpException('Player not found.');
        }

        $playerClub = $player->getClub();
        if ($playerClub === null || (int) $playerClub->getId() !== $clubId) {
            throw new NotFoundHttpException('Player not found.');
        }

        return $player;
    }
}
