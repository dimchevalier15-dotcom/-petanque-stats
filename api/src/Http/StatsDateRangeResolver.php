<?php

declare(strict_types=1);

namespace App\Http;

use App\ValueObject\DateRange;
use Symfony\Component\HttpFoundation\Request;

final class StatsDateRangeResolver
{
    public static function fromRequest(Request $request): ?DateRange
    {
        $from = $request->query->get('from');
        $to = $request->query->get('to');

        if ($from === null && $to === null) {
            return null;
        }

        if (!is_string($from) || !is_string($to) || $from === '' || $to === '') {
            throw new \InvalidArgumentException('Both from and to dates are required.');
        }

        $range = DateRange::fromQueryStrings($from, $to);
        $todayEnd = new \DateTimeImmutable('today')->setTime(23, 59, 59);
        if ($range->from > $todayEnd || $range->to > $todayEnd) {
            throw new \InvalidArgumentException('Date range cannot include future dates.');
        }

        return $range;
    }
}
