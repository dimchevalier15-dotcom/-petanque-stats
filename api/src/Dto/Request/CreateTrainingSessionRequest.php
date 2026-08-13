<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enum\TrainingType;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateTrainingSessionRequest
{
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [TrainingType::class, 'values'])]
    public string $type;

    #[Assert\NotNull]
    #[Assert\Range(min: 0.5, max: 30)]
    public float $distance;

    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 30)]
    public int $plannedBalls;
}
