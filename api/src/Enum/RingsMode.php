<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Variant of the "dans les ronds" exercise: pointing into the rings, or
 * shooting the target ball laid in each ring (where a carreau doubles points).
 */
enum RingsMode: string
{
    case POINT = 'point';
    case SHOOTING = 'shooting';

    public const CARREAU_MULTIPLIER = 2;
    public const THROWS_PER_SESSION = 15;

    public function allowsCarreau(): bool
    {
        return $this === self::SHOOTING;
    }

    public function maxSessionScore(): int
    {
        $maxThrow = RingsTarget::FAR->points() * ($this->allowsCarreau() ? self::CARREAU_MULTIPLIER : 1);

        return self::THROWS_PER_SESSION * $maxThrow;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $m) => $m->value, self::cases());
    }
}
