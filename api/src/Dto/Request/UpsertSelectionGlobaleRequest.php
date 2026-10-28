<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UpsertSelectionGlobaleRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $name = '';
}
