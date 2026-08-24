<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AuthToken;
use App\Entity\User;
use App\Enum\AuthTokenPurpose;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuthToken>
 */
final class AuthTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthToken::class);
    }

    public function findOneByHash(string $tokenHash): ?AuthToken
    {
        return $this->findOneBy(['tokenHash' => $tokenHash]);
    }

    public function invalidateOpenTokens(User $user, AuthTokenPurpose $purpose, DateTimeImmutable $now): void
    {
        $openTokens = $this->findBy([
            'user' => $user,
            'purpose' => $purpose,
            'usedAt' => null,
        ]);

        foreach ($openTokens as $token) {
            $token->markUsed($now);
        }
    }
}
