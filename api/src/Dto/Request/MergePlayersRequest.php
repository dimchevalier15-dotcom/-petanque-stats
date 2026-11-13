<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class MergePlayersRequest
{
    #[Assert\NotNull]
    #[Assert\Positive]
    public int $keepPlayerId = 0;

    #[Assert\NotNull]
    #[Assert\Positive]
    public int $dropPlayerId = 0;

    #[Assert\Positive]
    public ?int $keepUserId = null;
}
