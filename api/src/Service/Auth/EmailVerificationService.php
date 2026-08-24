<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Entity\User;
use App\Enum\AuthTokenPurpose;
use App\Service\Mail\TransactionalMailer;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

final class EmailVerificationService
{
    public function __construct(
        private AuthTokenIssuer $tokens,
        private TransactionalMailer $mailer,
        private EntityManagerInterface $em,
        private ClockInterface $clock,
    ) {
    }

    public function sendForUser(User $user): void
    {
        if ($user->isEmailVerified()) {
            return;
        }

        $plainToken = $this->tokens->issue(
            $user,
            AuthTokenPurpose::EmailVerification,
            new DateInterval('PT24H'),
        );
        $this->mailer->sendEmailVerification($user, $plainToken);
    }

    /**
     * @return 'verified'|'already_verified'|'invalid'
     */
    public function verify(string $plainToken): string
    {
        try {
            $token = $this->tokens->consume($plainToken, AuthTokenPurpose::EmailVerification);
        } catch (InvalidAuthTokenException) {
            return 'invalid';
        }

        $user = $token->getUser();
        if ($user->isEmailVerified()) {
            return 'already_verified';
        }

        $user->markEmailVerified($this->clock->now());
        $this->em->flush();

        return 'verified';
    }
}
