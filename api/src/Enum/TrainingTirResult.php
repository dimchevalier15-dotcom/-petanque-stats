<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Tir training result categories, aligned with match/shooting vocabulary
 * but scored independently (not the official precision-shooting barème).
 */
enum TrainingTirResult: string
{
    case MISSED = 'missed';
    case TOUCHED = 'touched';
    case SUCCESSFUL = 'successful';
    case PALET = 'palet';
    case CARREAU = 'carreau';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $r) => $r->value, self::cases());
    }

    /**
     * Results counted as successful for the success-rate KPI.
     *
     * @return list<string>
     */
    public static function successfulValues(): array
    {
        return [
            self::SUCCESSFUL->value,
            self::PALET->value,
            self::CARREAU->value,
        ];
    }
}
