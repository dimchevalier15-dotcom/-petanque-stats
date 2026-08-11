<?php

declare(strict_types=1);

namespace App\Tests\ValueObject;

use App\ValueObject\DateRange;
use PHPUnit\Framework\TestCase;

final class DateRangeTest extends TestCase
{
    public function testFromQueryStringsBuildsAnInclusiveRange(): void
    {
        $range = DateRange::fromQueryStrings('2026-01-01', '2026-01-31');

        self::assertSame('2026-01-01 00:00:00', $range->from->format('Y-m-d H:i:s'));
        self::assertSame('2026-01-31 23:59:59', $range->to->format('Y-m-d H:i:s'));
    }

    public function testFromQueryStringsRejectsInvalidDates(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        DateRange::fromQueryStrings('31-01-2026', '2026-01-31');
    }

    public function testConstructorRejectsInvertedRanges(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new DateRange(
            new \DateTimeImmutable('2026-02-01'),
            new \DateTimeImmutable('2026-01-01'),
        );
    }
}
