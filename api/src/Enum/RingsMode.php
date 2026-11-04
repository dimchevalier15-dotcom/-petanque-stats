<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Variant of the "point dans les ronds" exercise. Only the pointing variant
 * exists for now; a shooting variant will reuse the same sessions and throws.
 */
enum RingsMode: string
{
    case POINT = 'point';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $m) => $m->value, self::cases());
    }
}
