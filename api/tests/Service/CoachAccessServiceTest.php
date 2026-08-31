<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Club;
use App\Entity\Country;
use App\Entity\Player;
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
