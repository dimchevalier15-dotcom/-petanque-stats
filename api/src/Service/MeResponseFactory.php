<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Response\MeResponse;
use App\Entity\User;
use App\Repository\GameParticipantRepository;
use App\Repository\PlayerRepository;

final class MeResponseFactory
{
    public function __construct(
        private PlayerRepository $players,
        private GameParticipantRepository $participants,
    ) {
    }

    public function fromUser(User $user): MeResponse
    {
        $player = $this->players->findOneByUserId((int) $user->getId());
        $coachClub = $user->getCoachForClub();

        return new MeResponse(
            id: (int) $user->getId(),
            email: $user->getEmail(),
            playerId: $player?->getId() !== null ? (int) $player->getId() : null,
            firstName: $player?->getFirstName(),
            lastName: $player?->getLastName(),
            nickname: $player?->getNickname(),
            emailVerified: $user->isEmailVerified(),
            role: $user->getRole(),
            isAdmin: $user->isMaster(),
            coachForClubId: $coachClub?->getId() !== null ? (int) $coachClub->getId() : null,
            coachForClubName: $coachClub?->getName(),
            requiresMatchValidation: $user->requiresMatchValidation(),
            pendingValidationCount: $player?->getId() !== null
                ? $this->participants->countPendingValidationForPlayer((int) $player->getId())
                : 0,
        );
    }
}
