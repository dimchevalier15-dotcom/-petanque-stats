<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enum\PointingShotResult;
use Symfony\Component\Validator\Constraints as Assert;

final class CompletePointingSessionRequest
{
    /**
     * @var list<PointingShotInputDto>
     */
    #[Assert\Count(exactly: 20)]
    #[Assert\Valid]
    public array $shots = [];
}

final class PointingShotInputDto
{
    #[Assert\Range(min: 1, max: 5)]
    public int $workshop;

    #[Assert\Range(min: 6, max: 9)]
    public int $distance;

    #[Assert\NotBlank]
    #[Assert\Choice(callback: [PointingShotResult::class, 'values'])]
    public string $result;
}
