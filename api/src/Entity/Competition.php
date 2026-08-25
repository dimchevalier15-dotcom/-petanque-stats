<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CompetitionRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompetitionRepository::class)]
#[ORM\Table(name: 'competitions')]
class Competition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(name: 'event_date', type: 'date_immutable')]
    private DateTimeImmutable $eventDate;

    #[ORM\Column(type: 'string', length: 100)]
    private string $country;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $context = null;

    public function __construct(string $name, DateTimeImmutable $eventDate, string $country, ?string $context = null)
    {
        $this->name = $name;
        $this->eventDate = $eventDate;
        $this->country = $country;
        $this->context = $context;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEventDate(): DateTimeImmutable
    {
        return $this->eventDate;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setEventDate(DateTimeImmutable $eventDate): void
    {
        $this->eventDate = $eventDate;
    }

    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    public function setContext(?string $context): void
    {
        $this->context = $context;
    }
}
