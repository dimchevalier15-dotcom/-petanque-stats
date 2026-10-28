<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Player;
use App\Entity\User;
use App\Repository\PlayerRepository;
use App\Repository\SelectionGlobaleCoachRepository;
use App\Repository\SelectionGlobalePlayerRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CoachAccessService
{
    public function __construct(
        private PlayerRepository $players,
        private SelectionGlobaleCoachRepository $selectionCoaches,
        private SelectionGlobalePlayerRepository $selectionPlayers,
    ) {
    }

    public function requireCoachClubId(User $user): int
    {
        $club = $user->getCoachForClub();
        if ($club === null) {
            throw new AccessDeniedHttpException('Coach access required.');
        }

        return (int) $club->getId();
    }

    public function assertUserCoachesSelection(User $user, int $selectionGlobaleId): void
    {
        if (!$this->selectionCoaches->userCoachesSelection($user, $selectionGlobaleId)) {
            throw new AccessDeniedHttpException('Selection globale coach access required.');
        }
    }

    public function assertCoachCanViewPlayer(User $user, int $playerId, ?int $selectionGlobaleId = null): Player
    {
        if ($selectionGlobaleId !== null) {
            return $this->assertSelectionCoachCanViewPlayer($user, $playerId, $selectionGlobaleId);
        }

        return $this->assertClubCoachCanViewPlayer($user, $playerId);
    }

    private function assertClubCoachCanViewPlayer(User $user, int $playerId): Player
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

    private function assertSelectionCoachCanViewPlayer(User $user, int $playerId, int $selectionGlobaleId): Player
    {
        $this->assertUserCoachesSelection($user, $selectionGlobaleId);

        $player = $this->players->find($playerId);
        if ($player === null) {
            throw new NotFoundHttpException('Player not found.');
        }

        if (!$this->selectionPlayers->isPlayerInSelection($selectionGlobaleId, $playerId)) {
            throw new NotFoundHttpException('Player not found.');
        }

        return $player;
    }
}
