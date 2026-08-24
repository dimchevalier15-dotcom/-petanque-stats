<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\AuthTokenPurpose;
use App\Repository\AuthTokenRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuthTokenRepository::class)]
#[ORM\Table(name: 'auth_tokens')]
#[ORM\UniqueConstraint(name: 'UNIQ_AUTH_TOKENS_HASH', columns: ['token_hash'])]
#[ORM\Index(name: 'IDX_AUTH_TOKENS_USER_PURPOSE', columns: ['user_id', 'purpose'])]
class AuthToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 32, enumType: AuthTokenPurpose::class)]
    private AuthTokenPurpose $purpose;

    #[ORM\Column(name: 'token_hash', length: 64)]
    private string $tokenHash;

    #[ORM\Column(name: 'expires_at', type: 'datetime_immutable')]
    private DateTimeImmutable $expiresAt;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'used_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $usedAt = null;

    public function __construct(
        User $user,
        AuthTokenPurpose $purpose,
        string $tokenHash,
        DateTimeImmutable $expiresAt,
    ) {
        $this->user = $user;
        $this->purpose = $purpose;
        $this->tokenHash = $tokenHash;
        $this->expiresAt = $expiresAt;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getPurpose(): AuthTokenPurpose
    {
        return $this->purpose;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(DateTimeImmutable $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    public function getUsedAt(): ?DateTimeImmutable
    {
        return $this->usedAt;
    }

    public function isUsed(): bool
    {
        return $this->usedAt !== null;
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $this->expiresAt <= $now;
    }

    public function markUsed(DateTimeImmutable $now): void
    {
        $this->usedAt = $now;
    }
}
