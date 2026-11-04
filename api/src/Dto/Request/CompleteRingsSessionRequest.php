<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class CompleteRingsSessionRequest
{
    /**
     * Throws in the order they were played.
     *
     * @var list<RingsThrowInputDto>
     */
    #[Assert\Count(exactly: 15)]
    #[Assert\Valid]
    public array $throws = [];
}
