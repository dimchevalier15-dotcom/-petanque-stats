<?php

declare(strict_types=1);

namespace App\Enum;

enum ShootingContextNature: string
{
    case TRAINING = 'training';
    case COMPETITION = 'competition';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $n) => $n->value, self::cases());
    }
}
