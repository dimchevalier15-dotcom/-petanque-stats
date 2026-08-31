<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Player;
use App\Entity\User;

final class GameParticipantValidationResolver
{
    /**
     * Determines the initial hasValidatedMatch value for a new participation.
     * null = pending, true = immediately validated.
     */
    public function resolveInitialValue(Player $player, User $matchCreator): ?bool
    {
        $user = $player->getUser();
        if ($user === null || !$user->requiresMatchValidation()) {
            return true;
        }

        if ($user->getId() === $matchCreator->getId()) {
            return true;
        }

        return null;
    }
}
