<?php

declare(strict_types=1);

namespace App\Tests\Service\Tournament;

use App\Service\Tournament\TournamentLaneCatalog;
use PHPUnit\Framework\TestCase;

final class TournamentLaneCatalogTest extends TestCase
{
    public function testExcludedNumbersExtendTheLaneRange(): void
    {
        $lanes = (new TournamentLaneCatalog())->usableLaneNumbers(12, [5, 8]);

        self::assertSame([1, 2, 3, 4, 6, 7, 9, 10, 11, 12, 13, 14], $lanes);
    }

    public function testDuplicateExclusionsAreIgnored(): void
    {
        $catalog = new TournamentLaneCatalog();

        self::assertSame([2, 3], $catalog->usableLaneNumbers(2, [1, 1]));
        self::assertSame([1, 4], $catalog->normalizeExcluded([4, 1, 4]));
    }
}
