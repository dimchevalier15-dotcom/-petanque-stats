<?php

declare(strict_types=1);

namespace App\Enum;

enum DistanceBucket: string
{
    case UNDER_6 = 'under_6';
    case FROM_6_TO_7 = '6_7';
    case FROM_7_TO_8 = '7_8';
    case FROM_8_TO_9 = '8_9';
    case FROM_9_TO_10 = '9_10';
    case FROM_10_PLUS = '10_plus';

    public static function fromDistance(?float $distance): ?self
    {
        if ($distance === null) {
            return null;
        }

        return match (true) {
            $distance < 6.0 => self::UNDER_6,
            $distance < 7.0 => self::FROM_6_TO_7,
            $distance < 8.0 => self::FROM_7_TO_8,
            $distance < 9.0 => self::FROM_8_TO_9,
            $distance < 10.0 => self::FROM_9_TO_10,
            default => self::FROM_10_PLUS,
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::UNDER_6 => 0,
            self::FROM_6_TO_7 => 1,
            self::FROM_7_TO_8 => 2,
            self::FROM_8_TO_9 => 3,
            self::FROM_9_TO_10 => 4,
            self::FROM_10_PLUS => 5,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $bucket) => $bucket->value, self::cases());
    }
}
