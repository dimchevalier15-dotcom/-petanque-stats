<?php

declare(strict_types=1);

namespace App\Http;

use App\ValueObject\DateRange;
use Symfony\Component\HttpFoundation\Request;

final class CoachDateRangeResolver
{
    public static function fromRequest(Request $request): DateRange
    {
        try {
            $range = StatsDateRangeResolver::fromRequest($request);
            if ($range !== null) {
                return $range;
            }
        } catch (\InvalidArgumentException $e) {
            throw $e;
        }

        return DateRange::defaultLastMonth();
    }
}
