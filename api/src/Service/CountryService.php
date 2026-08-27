<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Response\CountryItem;
use App\Entity\Country;
use App\Repository\CountryRepository;

final class CountryService
{
    public function __construct(
        private CountryRepository $countries,
    ) {
    }

    /**
     * @return list<CountryItem>
     */
    public function listAll(): array
    {
        $out = [];
        foreach ($this->countries->findAllOrdered() as $country) {
            $out[] = $this->toItem($country);
        }

        return $out;
    }

    public function toItem(Country $country): CountryItem
    {
        return new CountryItem(
            id: (int) $country->getId(),
            isoCode: $country->getIsoCode(),
            name: $country->getName(),
        );
    }
}
