<?php

declare(strict_types=1);

namespace App\Http;

use App\ValueObject\DateRange;
use Symfony\Component\HttpFoundation\Request;

final class CoachDateRangeResolver
{
    /**
     * Returns null when no date filter is requested (all matches).
     * When from/to are provided, filters on match played_at.
     */
    public static function fromRequest(Request $request): ?DateRange
    {
        return StatsDateRangeResolver::fromRequest($request);
    }
}
