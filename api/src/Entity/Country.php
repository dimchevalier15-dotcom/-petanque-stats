<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CountryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CountryRepository::class)]
#[ORM\Table(name: 'countries')]
class Country
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'iso_code', type: 'string', length: 2, unique: true)]
    private string $isoCode;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    public function __construct(string $isoCode, string $name)
    {
        $this->isoCode = $isoCode;
        $this->name = $name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIsoCode(): string
    {
        return $this->isoCode;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
