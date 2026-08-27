<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Club;
use App\Entity\Country;
use PHPUnit\Framework\TestCase;

final class ClubTest extends TestCase
{
    public function testClubBelongsToACountryAndStartsWithoutPlayers(): void
    {
        $country = new Country('FR', 'France');
        $club = new Club('Pétanque Lyon', $country, 'Club municipal');

        self::assertSame('Pétanque Lyon', $club->getName());
        self::assertSame($country, $club->getCountry());
        self::assertSame('Club municipal', $club->getDescription());
        self::assertCount(0, $club->getPlayers());
    }
}
