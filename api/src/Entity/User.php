<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\UserRole;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    private string $password;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'email_verified_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $emailVerifiedAt = null;

    #[ORM\Column(type: 'string', length: 20, enumType: UserRole::class)]
    private UserRole $role;

    #[ORM\ManyToOne(targetEntity: Club::class)]
    #[ORM\JoinColumn(name: 'coach_for_club_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Club $coachForClub = null;

    public function __construct(string $email)
    {
        $this->email = $email;
        $this->createdAt = new DateTimeImmutable();
        $this->password = '';
        $this->role = UserRole::SIMPLE_PLAYER;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $hashedPassword): void
    {
        $this->password = $hashedPassword;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getEmailVerifiedAt(): ?DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    public function markEmailVerified(DateTimeImmutable $verifiedAt): void
    {
        if ($this->emailVerifiedAt === null) {
            $this->emailVerifiedAt = $verifiedAt;
        }
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function setRole(UserRole $role): void
    {
        $this->role = $role;
    }

    public function isMaster(): bool
    {
        return $this->role === UserRole::MASTER;
    }

    public function getCoachForClub(): ?Club
    {
        return $this->coachForClub;
    }

    public function setCoachForClub(?Club $club): void
    {
        $this->coachForClub = $club;
    }

    public function isCoach(): bool
    {
        return $this->coachForClub !== null;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];
        if ($this->isMaster()) {
            $roles[] = 'ROLE_ADMIN';
        }

        return $roles;
    }

    public function eraseCredentials(): void
    {
    }
}
