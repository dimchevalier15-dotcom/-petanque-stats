<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enum\ShootingShotResult;
use Symfony\Component\Validator\Constraints as Assert;

final class CompleteShootingSessionRequest
{
    /**
     * Exactly 20 shots are expected: 5 workshops x 4 distances.
     * Structural validation (no duplicate, all combinations covered) happens
     * in the service, where the exact error messages matter for the client.
     *
     * @var list<ShootingShotInputDto>
     */
    #[Assert\Count(exactly: 20)]
    #[Assert\Valid]
    public array $shots = [];
}

final class ShootingShotInputDto
{
    #[Assert\Range(min: 1, max: 5)]
    public int $workshop;

    #[Assert\Range(min: 6, max: 9)]
    public int $distance;

    #[Assert\NotBlank]
    #[Assert\Choice(callback: [ShootingShotResult::class, 'values'])]
    public string $result;
}
