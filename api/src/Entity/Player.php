<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PlayerRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerRepository::class)]
#[ORM\Table(name: 'players')]
class Player
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'first_name', type: 'string', length: 100)]
    private string $firstName;

    #[ORM\Column(name: 'last_name', type: 'string', length: 100)]
    private string $lastName;

    #[ORM\Column(name: 'nickname', type: 'string', length: 100)]
    private string $nickname;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: Club::class, inversedBy: 'players')]
    #[ORM\JoinColumn(name: 'club_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Club $club = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, unique: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(name: 'placeholder_key', type: 'string', length: 1, nullable: true, unique: true)]
    private ?string $placeholderKey = null;

    public function __construct(string $firstName, string $lastName, string $nickname)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->nickname = $nickname;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getNickname(): string
    {
        return $this->nickname;
    }

    public function setNickname(string $nickname): void
    {
        $this->nickname = $nickname;
    }

    public function getClub(): ?Club
    {
        return $this->club;
    }

    public function setClub(?Club $club): void
    {
        $this->club = $club;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getPlaceholderKey(): ?string
    {
        return $this->placeholderKey;
    }

    public function isPlaceholder(): bool
    {
        return $this->placeholderKey !== null;
    }
}
