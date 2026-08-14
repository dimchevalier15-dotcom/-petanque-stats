<?php

declare(strict_types=1);

namespace App\Enum;

enum PlayerRole: string
{
    case POINTEUR = 'pointeur';
    case MILIEU = 'milieu';
    case TIREUR = 'tireur';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $role) => $role->value, self::cases());
    }

    public function defaultShotType(): string
    {
        return $this === self::TIREUR ? 'tir' : 'point';
    }
}
