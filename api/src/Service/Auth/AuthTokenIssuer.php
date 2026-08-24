<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Entity\AuthToken;
use App\Entity\User;
use App\Enum\AuthTokenPurpose;
use App\Repository\AuthTokenRepository;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

final class AuthTokenIssuer
{
    public function __construct(
        private AuthTokenRepository $tokens,
        private EntityManagerInterface $em,
        private ClockInterface $clock,
    ) {
    }

    public function issue(User $user, AuthTokenPurpose $purpose, DateInterval $ttl): string
    {
        $now = $this->clock->now();
        $this->tokens->invalidateOpenTokens($user, $purpose, $now);

        $plainToken = bin2hex(random_bytes(32));
        $token = new AuthToken(
            user: $user,
            purpose: $purpose,
            tokenHash: hash('sha256', $plainToken),
            expiresAt: $now->add($ttl),
        );
        $this->em->persist($token);
        $this->em->flush();

        return $plainToken;
    }

    public function consume(string $plainToken, AuthTokenPurpose $purpose): AuthToken
    {
        $plainToken = trim($plainToken);
        if ($plainToken === '' || !preg_match('/^[a-f0-9]{64}$/', $plainToken)) {
            throw new InvalidAuthTokenException();
        }

        $token = $this->tokens->findOneByHash(hash('sha256', $plainToken));
        $now = $this->clock->now();
        if (
            $token === null
            || $token->getPurpose() !== $purpose
            || $token->isUsed()
            || $token->isExpired($now)
        ) {
            throw new InvalidAuthTokenException();
        }

        $token->markUsed($now);
        $this->em->flush();

        return $token;
    }
}
