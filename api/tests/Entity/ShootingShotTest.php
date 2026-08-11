<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Player;
use App\Entity\ShootingShot;
use App\Entity\ShootingSession;
use App\Enum\ShootingDistance;
use App\Enum\ShootingShotResult;
use App\Enum\ShootingWorkshop;
use PHPUnit\Framework\TestCase;

final class ShootingShotTest extends TestCase
{
    public function testAShotBelongsToItsSessionAndCarriesAllRequiredData(): void
    {
        $session = new ShootingSession(new Player('Jean', 'Bernard', 'Jeannot'));

        $shot = new ShootingShot(
            $session,
            ShootingWorkshop::BETWEEN_TWO_BALLS,
            ShootingDistance::EIGHT_METERS,
            ShootingShotResult::SUCCESSFUL,
            3,
        );

        self::assertSame($session, $shot->getSession());
        self::assertSame(ShootingWorkshop::BETWEEN_TWO_BALLS, $shot->getWorkshop());
        self::assertSame(ShootingDistance::EIGHT_METERS, $shot->getDistance());
        self::assertSame(ShootingShotResult::SUCCESSFUL, $shot->getResult());
        self::assertSame(3, $shot->getScore());
    }

    public function testACompleteSessionHasExactlyTwentyShotsCoveringEveryWorkshopAndDistance(): void
    {
        $session = new ShootingSession(new Player('Jean', 'Bernard', 'Jeannot'));

        $shots = [];
        foreach (ShootingWorkshop::all() as $workshop) {
            foreach (ShootingDistance::all() as $distance) {
                $shots[] = new ShootingShot($session, $workshop, $distance, ShootingShotResult::MISSED, 0);
            }
        }

        self::assertCount(20, $shots);
        self::assertCount(5, ShootingWorkshop::all());
        self::assertCount(4, ShootingDistance::all());
    }
}
