<?php

declare(strict_types=1);

namespace App\Enum;

enum GameType: string
{
    case TETE_A_TETE = 'tete_a_tete';
    case DOUBLETTE = 'doublette';
    case TRIPLETTE = 'triplette';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $t) => $t->value, self::cases());
    }

    public function playersPerTeam(): int
    {
        return match ($this) {
            self::TETE_A_TETE => 1,
            self::DOUBLETTE => 2,
            self::TRIPLETTE => 3,
        };
    }

    public function maxPointsPerEnd(): int
    {
        return $this === self::TETE_A_TETE ? 3 : 6;
    }
}
