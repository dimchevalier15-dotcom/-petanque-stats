<?php

declare(strict_types=1);

namespace App\Tests\Service\Rings;

use App\Service\Rings\RingsScoreDecileCalculator;
use PHPUnit\Framework\TestCase;

final class RingsScoreDecileCalculatorTest extends TestCase
{
    public function testMapsScoresIntoTenBands(): void
    {
        $calc = new RingsScoreDecileCalculator(75);

        self::assertSame(1, $calc->decileForScore(0));
        self::assertSame(1, $calc->decileForScore(6));
        self::assertSame(2, $calc->decileForScore(7));
        self::assertSame(10, $calc->decileForScore(75));
        self::assertSame(10, $calc->decileForScore(74));
    }

    public function testDistributionCountsSessions(): void
    {
        $calc = new RingsScoreDecileCalculator(75);
        $distribution = $calc->buildDistribution([0, 7, 75, 75]);

        self::assertCount(10, $distribution);
        self::assertSame(1, $distribution[0]['sessionCount']);
        self::assertSame(1, $distribution[1]['sessionCount']);
        self::assertSame(2, $distribution[9]['sessionCount']);
        self::assertSame(0, $distribution[0]['minScore']);
        self::assertSame(75, $distribution[9]['maxScore']);
    }
}
