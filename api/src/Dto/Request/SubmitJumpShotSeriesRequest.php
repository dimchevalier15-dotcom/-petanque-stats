<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enum\JumpShotThrowResult;
use Symfony\Component\Validator\Constraints as Assert;

final class JumpShotThrowInputDto
{
    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 3)]
    public ?int $position = null;

    #[Assert\NotBlank]
    #[Assert\Choice(callback: [JumpShotThrowResult::class, 'values'])]
    public ?string $result = null;
}

final class SubmitJumpShotSeriesRequest
{
    /**
     * @var list<JumpShotThrowInputDto>
     */
    #[Assert\NotNull]
    #[Assert\Count(exactly: 3)]
    #[Assert\Valid]
    public array $throws = [];
}
