<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * The three 50 cm rings of the exercise, each at a fixed distance from the
 * throwing circle and worth a fixed number of points when succeeded.
 */
enum RingsTarget: string
{
    case NEAR = 'near';
    case MIDDLE = 'middle';
    case FAR = 'far';

    public function points(): int
    {
        return match ($this) {
            self::NEAR => 1,
            self::MIDDLE => 3,
            self::FAR => 5,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $t) => $t->value, self::cases());
    }
}
