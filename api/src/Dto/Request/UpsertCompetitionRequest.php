<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UpsertCompetitionRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $name;

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d{4}-\d{2}-\d{2}$/', message: 'Date must use YYYY-MM-DD format.')]
    public string $eventDate;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $country;

    #[Assert\Length(max: 255)]
    public ?string $context = null;
}
