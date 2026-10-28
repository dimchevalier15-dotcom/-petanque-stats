<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Club;
use App\Entity\Country;
use App\Entity\Player;
use App\Entity\SelectionGlobale;
use App\Entity\SelectionGlobaleCoach;
use App\Entity\SelectionGlobalePlayer;
use App\Entity\User;
use App\Service\CoachAccessService;
use Doctrine\ORM\EntityManagerInterface;
use App\Tests\Support\KernelDatabaseTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CoachAccessServiceTest extends KernelDatabaseTestCase
{
    private EntityManagerInterface $em;
    private CoachAccessService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->service = $container->get(CoachAccessService::class);
    }

    public function testRequireCoachClubIdThrowsWhenNotCoach(): void
    {
        $user = new User('coach-access-'.bin2hex(random_bytes(4)).'@test.fr');

        $this->expectException(AccessDeniedHttpException::class);
        $this->service->requireCoachClubId($user);
    }

    public function testAssertCoachCanViewPlayerAllowsSelectionRoster(): void
    {
        [$selection, $coach, $player] = $this->createSelectionCoachAndPlayer();

        $found = $this->service->assertCoachCanViewPlayer($coach, (int) $player->getId(), (int) $selection->getId());

        self::assertSame($player->getId(), $found->getId());
    }

    public function testAssertCoachCanViewPlayerRejectsPlayerOutsideSelection(): void
    {
        [$selection, $coach, , $otherPlayer] = $this->createSelectionCoachAndPlayer(withExtraPlayer: true);

        $this->expectException(NotFoundHttpException::class);
        $this->service->assertCoachCanViewPlayer($coach, (int) $otherPlayer->getId(), (int) $selection->getId());
    }

    public function testAssertCoachCanViewPlayerRejectsOtherClub(): void
    {
        [$clubA, , $player] = $this->createTwoClubsAndPlayerInSecondClub();

        $user = new User('coach-access-'.bin2hex(random_bytes(4)).'@test.fr');
        $user->setCoachForClub($clubA);
        $this->em->persist($user);
        $this->em->flush();

        $this->expectException(NotFoundHttpException::class);
        $this->service->assertCoachCanViewPlayer($user, (int) $player->getId());
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

    /**
     * @return array{0:SelectionGlobale,1:User,2:Player,3?:Player}
     */
    private function createSelectionCoachAndPlayer(bool $withExtraPlayer = false): array
    {
        $suffix = bin2hex(random_bytes(4));
        $country = $this->createUniqueCountry('Country '.$suffix);
        $club = new Club('Club '.$suffix, $country);
        $selection = new SelectionGlobale('Selection '.$suffix);
        $coach = new User('sel-coach-'.$suffix.'@test.fr');
        $player = new Player('Paul', 'Martin', '');
        $player->setClub($club);

        $this->em->persist($club);
        $this->em->persist($selection);
        $this->em->persist($coach);
        $this->em->persist($player);
        $this->em->persist(new SelectionGlobaleCoach($selection, $coach));
        $this->em->persist(new SelectionGlobalePlayer($selection, $player));

        $extra = null;
        if ($withExtraPlayer) {
            $extra = new Player('Other', 'Player', '');
            $this->em->persist($extra);
        }

        $this->em->flush();

        return $withExtraPlayer ? [$selection, $coach, $player, $extra] : [$selection, $coach, $player];
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
