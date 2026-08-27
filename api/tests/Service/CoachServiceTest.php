<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Club;
use App\Entity\Country;
use App\Entity\Player;
use App\Entity\User;
use App\Repository\GameBallRepository;
use App\Repository\GameRepository;
use App\Repository\PlayerRepository;
use App\Service\CoachAccessService;
use App\Service\CoachService;
use App\Service\PlayerAlreadyHasClubException;
use App\Service\PlayerItemMapper;
use App\ValueObject\DateRange;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CoachAccessServiceTest extends TestCase
{
    public function testRequireCoachClubIdThrowsWhenNotCoach(): void
    {
        $service = new CoachAccessService($this->createMock(PlayerRepository::class));
        $user = new User('coach@test.fr');

        $this->expectException(AccessDeniedHttpException::class);
        $service->requireCoachClubId($user);
    }

    public function testAssertCoachCanViewPlayerRejectsOtherClub(): void
    {
        $country = new Country('FR', 'France');
        $clubA = new Club('Club A', $country);
        $clubB = new Club('Club B', $country);

        $player = new Player('Jean', 'Dupont', 'Jojo');
        $player->setClub($clubB);

        $players = $this->createMock(PlayerRepository::class);
        $players->method('find')->willReturn($player);

        $user = new User('coach@test.fr');
        $user->setCoachForClub($clubA);

        $service = new CoachAccessService($players);

        $this->expectException(NotFoundHttpException::class);
        $service->assertCoachCanViewPlayer($user, 1);
    }
}

final class CoachServiceTest extends TestCase
{
    public function testListPlayersForCoachReturnsShotSummaries(): void
    {
        $country = new Country('FR', 'France');
        $club = new Club('Test Club', $country);

        $player = new Player('Marie', 'Martin', 'Mimi');
        $player->setClub($club);

        $players = $this->createMock(PlayerRepository::class);
        $players->method('findByClubId')->willReturn([$player]);

        $games = $this->createMock(GameRepository::class);
        $games->method('findCompletedGamesForPlayer')->willReturn([]);

        $balls = $this->createMock(GameBallRepository::class);
        $mapper = new PlayerItemMapper();
        $em = $this->createMock(EntityManagerInterface::class);

        $user = new User('coach@test.fr');
        $user->setCoachForClub($club);

        $service = new CoachService($players, $games, $balls, $mapper, $em);
        $range = DateRange::defaultLastMonth();

        $res = $service->listPlayersForCoach($user, $range);

        self::assertSame('Test Club', $res->clubName);
        self::assertCount(1, $res->items);
        self::assertSame('Marie', $res->items[0]->firstName);
        self::assertNull($res->items[0]->point->average);
        self::assertNull($res->items[0]->point->successCount);
    }

    public function testAttachPlayerToCoachClubAssignsClub(): void
    {
        $country = new Country('FR', 'France');
        $club = new Club('Test Club', $country);
        $player = new Player('Paul', 'Durand', 'Paulo');

        $players = $this->createMock(PlayerRepository::class);
        $players->method('find')->willReturn($player);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $user = new User('coach@test.fr');
        $user->setCoachForClub($club);

        $service = new CoachService(
            $players,
            $this->createMock(GameRepository::class),
            $this->createMock(GameBallRepository::class),
            new PlayerItemMapper(),
            $em,
        );

        $item = $service->attachPlayerToCoachClub($user, 1);

        self::assertSame('Paul', $item->firstName);
        self::assertSame($club, $player->getClub());
        self::assertSame('Test Club', $item->clubName);
    }

    public function testAttachPlayerToCoachClubRejectsPlayerWithClub(): void
    {
        $country = new Country('FR', 'France');
        $club = new Club('Test Club', $country);
        $otherClub = new Club('Other', $country);
        $player = new Player('Paul', 'Durand', 'Paulo');
        $player->setClub($otherClub);

        $players = $this->createMock(PlayerRepository::class);
        $players->method('find')->willReturn($player);

        $user = new User('coach@test.fr');
        $user->setCoachForClub($club);

        $service = new CoachService(
            $players,
            $this->createMock(GameRepository::class),
            $this->createMock(GameBallRepository::class),
            new PlayerItemMapper(),
            $this->createMock(EntityManagerInterface::class),
        );

        $this->expectException(PlayerAlreadyHasClubException::class);
        $service->attachPlayerToCoachClub($user, 1);
    }
}
