<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Request\UpsertClubRequest;
use App\Dto\Response\ClubItem;
use App\Entity\Club;
use App\Repository\ClubRepository;
use App\Repository\CountryRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ClubService
{
    public function __construct(
        private ClubRepository $clubs,
        private CountryRepository $countries,
        private CountryService $countryService,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @return list<ClubItem>
     */
    public function listAll(): array
    {
        $out = [];
        foreach ($this->clubs->findAllOrdered() as $club) {
            $out[] = $this->toItem($club);
        }

        return $out;
    }

    public function create(UpsertClubRequest $req): ClubItem
    {
        $country = $this->countries->find($req->countryId);
        if ($country === null) {
            throw new CountryNotFoundException();
        }

        $club = new Club(
            name: trim($req->name),
            country: $country,
            description: $this->normalizeOptionalString($req->description),
        );

        $this->em->persist($club);
        $this->em->flush();

        return $this->toItem($club);
    }

    public function update(Club $club, UpsertClubRequest $req): ClubItem
    {
        $country = $this->countries->find($req->countryId);
        if ($country === null) {
            throw new CountryNotFoundException();
        }

        $club->setName(trim($req->name));
        $club->setCountry($country);
        $club->setDescription($this->normalizeOptionalString($req->description));

        $this->em->flush();

        return $this->toItem($club);
    }

    public function delete(Club $club): void
    {
        $this->em->remove($club);
        $this->em->flush();
    }

    private function toItem(Club $club): ClubItem
    {
        $country = $club->getCountry();

        return new ClubItem(
            id: (int) $club->getId(),
            name: $club->getName(),
            description: $club->getDescription(),
            country: $this->countryService->toItem($country),
        );
    }

    private function normalizeOptionalString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}

final class CountryNotFoundException extends \RuntimeException
{
}
