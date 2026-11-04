<?php

declare(strict_types=1);

namespace App\Enum;

enum JumpShotProgressionMode: string
{
    case EASY = 'easy';
    case STANDARD = 'standard';
    case HARD = 'hard';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $m) => $m->value, self::cases());
    }
}
