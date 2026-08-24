<?php

declare(strict_types=1);

namespace App\Tests\Enum;

use App\Enum\GameType;
use PHPUnit\Framework\TestCase;

final class GameTypeTest extends TestCase
{
    public function testMaxPointsPerEndDependsOnFormat(): void
    {
        self::assertSame(3, GameType::TETE_A_TETE->maxPointsPerEnd());
        self::assertSame(6, GameType::DOUBLETTE->maxPointsPerEnd());
        self::assertSame(6, GameType::TRIPLETTE->maxPointsPerEnd());
    }
}
