<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Player;
use App\Entity\ShootingSession;
use App\Enum\ShootingContextNature;
use LogicException;
use PHPUnit\Framework\TestCase;

final class ShootingSessionTest extends TestCase
{
    public function testASessionBelongsToItsPlayer(): void
    {
        $player = new Player('Jean', 'Bernard', 'Jeannot');
        $session = new ShootingSession($player);

        self::assertSame($player, $session->getPlayer());
        self::assertTrue($session->belongsTo($player));
    }

    public function testAPlayerCanHaveSeveralSessions(): void
    {
        $player = new Player('Jean', 'Bernard', 'Jeannot');

        $session1 = new ShootingSession($player);
        $session2 = new ShootingSession($player);

        self::assertTrue($session1->belongsTo($player));
        self::assertTrue($session2->belongsTo($player));
        self::assertNotSame($session1, $session2);
    }

    public function testADifferentPlayerDoesNotOwnTheSession(): void
    {
        $owner = new Player('Jean', 'Bernard', 'Jeannot');
        $other = new Player('Marie', 'Dupont', 'Mimi');
        $session = new ShootingSession($owner);

        self::assertFalse($session->belongsTo($other));
    }

    public function testANewSessionIsNotFinished(): void
    {
        $session = new ShootingSession(new Player('Jean', 'Bernard', 'Jeannot'));

        self::assertFalse($session->isFinished());
        self::assertNull($session->getFinishedAt());
        self::assertNull($session->getTotalScore());
    }

    public function testMarkingFinishedRecordsTheTotalScore(): void
    {
        $session = new ShootingSession(new Player('Jean', 'Bernard', 'Jeannot'));

        $session->markFinished(42);

        self::assertTrue($session->isFinished());
        self::assertSame(42, $session->getTotalScore());
        self::assertNotNull($session->getFinishedAt());
        self::assertNotNull($session->getPlayedAt());
    }

    public function testASessionCannotBeFinishedTwice(): void
    {
        $session = new ShootingSession(new Player('Jean', 'Bernard', 'Jeannot'));
        $session->markFinished(42);

        $this->expectException(LogicException::class);
        $session->markFinished(10);
    }

    public function testANewSessionHasNoContext(): void
    {
        $session = new ShootingSession(new Player('Jean', 'Bernard', 'Jeannot'));

        self::assertNull($session->getTitle());
        self::assertNull($session->getDescription());
    }

    public function testContextCanBeSetOnASession(): void
    {
        $session = new ShootingSession(new Player('Jean', 'Bernard', 'Jeannot'));

        $session->setContext(ShootingContextNature::TRAINING, 'Entraînement du soir', 'Bon ressenti, terrain sec.');

        self::assertSame(ShootingContextNature::TRAINING, $session->getContextNature());
        self::assertSame('Entraînement du soir', $session->getTitle());
        self::assertSame('Bon ressenti, terrain sec.', $session->getDescription());
    }
}
