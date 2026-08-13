<?php

declare(strict_types=1);

namespace App\Enum;

enum TrainingType: string
{
    case POINT = 'point';
    case TIR = 'tir';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $t) => $t->value, self::cases());
    }
}
