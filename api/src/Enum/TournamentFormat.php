<?php

declare(strict_types=1);

namespace App\Enum;

enum TournamentFormat: string
{
    case CHAMPIONSHIP_SINGLE_PLAYOFFS_8 = 'championship_single_playoffs_8';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $format): string => $format->value, self::cases());
    }
}
