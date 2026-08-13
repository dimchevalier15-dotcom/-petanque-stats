<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Game;
use App\Entity\GameBall;
use App\Entity\GameEnd;
use App\Entity\Player;
use App\Enum\GameType;
use PHPUnit\Framework\TestCase;

final class GameBallTest extends TestCase
{
    public function testABallCanBeRecordedWithoutADistance(): void
    {
        $ball = new GameBall($this->end(), $this->player(), 0, 1, 'point');

        self::assertNull($ball->getDistance());
    }

    public function testABallCanBeRecordedWithADistance(): void
    {
        $ball = new GameBall($this->end(), $this->player(), 0, 1, 'point', 7.2);

        self::assertSame(7.2, $ball->getDistance());
    }

    public function testDistanceIsIndependentFromOtherBallsOfTheSameEnd(): void
    {
        $end = $this->end();
        $player = $this->player();

        $ball1 = new GameBall($end, $player, 0, 1, 'point', 7.2);
        $ball2 = new GameBall($end, $player, 1, 2, 'tir', null);
        $ball3 = new GameBall($end, $player, 2, -1, 'point', 6.8);

        self::assertSame(7.2, $ball1->getDistance());
        self::assertNull($ball2->getDistance());
        self::assertSame(6.8, $ball3->getDistance());
    }

    private function end(): GameEnd
    {
        $game = new Game(GameType::DOUBLETTE, 13, 'standard');

        return new GameEnd($game, 1, 'A', 0);
    }

    private function player(): Player
    {
        return new Player('Jean', 'Bernard', 'Jeannot');
    }
}
