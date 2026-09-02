<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\LiveMatch;
use PHPUnit\Framework\TestCase;

final class LiveMatchTest extends TestCase
{
    public function testLiveMatchStoresUuidStatusAndData(): void
    {
        $data = [
            'type' => 'doublette',
            'teamA' => [1, 2],
            'teamB' => [3, 4],
            'ends' => [],
            'currentEndIndex' => 0,
        ];

        $liveMatch = new LiveMatch('550e8400-e29b-41d4-a716-446655440000', $data);

        self::assertSame('550e8400-e29b-41d4-a716-446655440000', $liveMatch->getUuid());
        self::assertSame(LiveMatch::STATUS_ACTIVE, $liveMatch->getStatus());
        self::assertSame($data, $liveMatch->getData());
        self::assertNull($liveMatch->getFinishedAt());

        $updated = ['ends' => [['index' => 1, 'balls' => []]]];
        $liveMatch->replaceData($updated);

        self::assertSame($updated, $liveMatch->getData());
        self::assertGreaterThanOrEqual($liveMatch->getCreatedAt(), $liveMatch->getUpdatedAt());
    }

    public function testFinishMarksLiveMatchAsFinishedAndPreventsFurtherUpdates(): void
    {
        $liveMatch = new LiveMatch('550e8400-e29b-41d4-a716-446655440000', ['scoreA' => 13]);

        $liveMatch->finish();

        self::assertSame(LiveMatch::STATUS_FINISHED, $liveMatch->getStatus());
        self::assertNotNull($liveMatch->getFinishedAt());

        $this->expectException(\DomainException::class);
        $liveMatch->replaceData(['scoreA' => 14]);
    }

    public function testSyncTimerStoresAccumulatedAndRunningSince(): void
    {
        $liveMatch = new LiveMatch('550e8400-e29b-41d4-a716-446655440000', ['scoreA' => 0]);
        $runningSince = new \DateTimeImmutable('2026-09-02T10:00:00+00:00');

        self::assertSame(0, $liveMatch->getTimerAccumulatedMs());
        self::assertNull($liveMatch->getTimerStartedAt());

        $liveMatch->syncTimer(12_000, $runningSince);

        self::assertSame(12_000, $liveMatch->getTimerAccumulatedMs());
        self::assertTrue($liveMatch->isTimerRunning());
        self::assertSame($runningSince, $liveMatch->getTimerStartedAt());

        $liveMatch->syncTimer(45_000, null);

        self::assertSame(45_000, $liveMatch->getTimerAccumulatedMs());
        self::assertFalse($liveMatch->isTimerRunning());
        self::assertNull($liveMatch->getTimerStartedAt());
    }

    public function testSyncTimerFailsWhenFinished(): void
    {
        $liveMatch = new LiveMatch('550e8400-e29b-41d4-a716-446655440000', ['scoreA' => 13]);
        $liveMatch->finish();

        $this->expectException(\DomainException::class);
        $liveMatch->syncTimer(1000, new \DateTimeImmutable());
    }
}
