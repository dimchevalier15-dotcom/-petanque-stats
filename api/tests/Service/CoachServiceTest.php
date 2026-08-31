<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Club;
use App\Entity\Country;
use App\Entity\Player;
use App\Entity\User;
use App\Service\CoachService;
use App\Service\PlayerAlreadyHasClubException;
use App\ValueObject\DateRange;
use Doctrine\ORM\EntityManagerInterface;
use App\Tests\Support\KernelDatabaseTestCase;

final class CoachServiceTest extends KernelDatabaseTestCase
{
    private EntityManagerInterface $em;
    private CoachService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->service = $container->get(CoachService::class);
    }

    public function testListPlayersForCoachReturnsShotSummaries(): void
    {
        [$club, $coach, $player] = $this->createCoachWithClubPlayer();
        $range = DateRange::fromQueryStrings('2026-01-01', '2026-01-31');

        $res = $this->service->listPlayersForCoach($coach, $range);

        self::assertSame('Test Club', $res->clubName);
        self::assertSame('2026-01-01', $res->from);
        self::assertSame('2026-01-31', $res->to);
        self::assertCount(1, $res->items);
        self::assertSame('Marie', $res->items[0]->firstName);
        self::assertSame((int) $player->getId(), $res->items[0]->id);
        self::assertNull($res->items[0]->point->average);
        self::assertNull($res->items[0]->point->successCount);
        self::assertSame($club->getName(), $res->clubName);
    }

    public function testListPlayersForCoachWithoutDateRangeDoesNotFilterByDate(): void
    {
        [, $coach] = $this->createCoachWithClubPlayer();

        $res = $this->service->listPlayersForCoach($coach, null);

        self::assertNull($res->from);
        self::assertNull($res->to);
        self::assertCount(1, $res->items);
    }

    public function testAttachPlayerToCoachClubAssignsClub(): void
    {
        [$club, $coach] = $this->createCoachWithClub();
        $player = $this->createPlayer('Paul', 'Durand', 'Paulo');

        $item = $this->service->attachPlayerToCoachClub($coach, (int) $player->getId());

        self::assertSame('Paul', $item->firstName);
        self::assertSame($club, $player->getClub());
        self::assertSame('Test Club', $item->clubName);
    }

    public function testAttachPlayerToCoachClubRejectsPlayerWithClub(): void
    {
        [, $otherClub, $player] = $this->createTwoClubsAndPlayerInSecondClub();
        [, $coach] = $this->createCoachWithClub();

        self::assertSame($otherClub, $player->getClub());

        $this->expectException(PlayerAlreadyHasClubException::class);
        $this->service->attachPlayerToCoachClub($coach, (int) $player->getId());
    }

    /**
     * @return array{0:Club,1:User}
     */
    private function createCoachWithClub(): array
    {
        $suffix = bin2hex(random_bytes(4));
        $country = $this->createUniqueCountry('Country '.$suffix);
        $club = new Club('Test Club', $country);
        $coach = new User('coach-'.$suffix.'@test.fr');
        $coach->setCoachForClub($club);

        $this->em->persist($club);
        $this->em->persist($coach);
        $this->em->flush();

        return [$club, $coach];
    }

    /**
     * @return array{0:Club,1:User,2:Player}
     */
    private function createCoachWithClubPlayer(): array
    {
        [$club, $coach] = $this->createCoachWithClub();
        $player = $this->createPlayer('Marie', 'Martin', 'Mimi');
        $player->setClub($club);
        $this->em->flush();

        return [$club, $coach, $player];
    }

    private function createPlayer(string $firstName, string $lastName, string $nickname): Player
    {
        $player = new Player($firstName, $lastName, $nickname);
        $this->em->persist($player);
        $this->em->flush();

        return $player;
    }

    /**
     * @return array{0:Club,1:Club,2:Player}
     */
    private function createTwoClubsAndPlayerInSecondClub(): array
    {
        $suffix = bin2hex(random_bytes(4));
        $country = $this->createUniqueCountry('Country '.$suffix);
        $clubA = new Club('Club A '.$suffix, $country);
        $clubB = new Club('Club B '.$suffix, $country);
        $player = new Player('Jean', 'Dupont', 'Jojo');
        $player->setClub($clubB);

        $this->em->persist($clubA);
        $this->em->persist($clubB);
        $this->em->persist($player);
        $this->em->flush();

        return [$clubA, $clubB, $player];
    }

    private function createUniqueCountry(string $name): Country
    {
        for ($attempt = 0; $attempt < 20; ++$attempt) {
            $isoCode = strtoupper(substr(bin2hex(random_bytes(4)), $attempt, 2));
            $existing = $this->em->getRepository(Country::class)->findOneBy(['isoCode' => $isoCode]);
            if ($existing === null) {
                $country = new Country($isoCode, $name);
                $this->em->persist($country);

                return $country;
            }
        }

        throw new \RuntimeException('Could not generate a unique country ISO code.');
    }
}
