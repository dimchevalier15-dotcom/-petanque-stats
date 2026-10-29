<?php

declare(strict_types=1);

namespace App\Enum;

enum JumpShotThrowResult: string
{
    case MISSED = 'missed';
    case CHIQUE = 'chique';
    case HIT = 'hit';
    case CARREAU = 'carreau';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }

    public function isSuccess(): bool
    {
        return $this !== self::MISSED;
    }
}
