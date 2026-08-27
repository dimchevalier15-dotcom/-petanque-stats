<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Club;
use App\Repository\ClubRepository;

final class PlayerClubResolver
{
    public function __construct(
        private ClubRepository $clubs,
    ) {
    }

    public function resolveOptional(?int $clubId): ?Club
    {
        if ($clubId === null) {
            return null;
        }

        $club = $this->clubs->find($clubId);
        if ($club === null) {
            throw new ClubNotFoundException();
        }

        return $club;
    }
}

final class ClubNotFoundException extends \RuntimeException
{
}
