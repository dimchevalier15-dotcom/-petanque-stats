<?php

declare(strict_types=1);

namespace App\Service\Auth;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class AuthRateLimiter
{
    public function __construct(
        #[Autowire(service: 'limiter.auth_forgot_password_ip')]
        private RateLimiterFactory $forgotPasswordIpLimiter,
        #[Autowire(service: 'limiter.auth_forgot_password_email')]
        private RateLimiterFactory $forgotPasswordEmailLimiter,
        #[Autowire(service: 'limiter.auth_resend_verification')]
        private RateLimiterFactory $resendVerificationLimiter,
    ) {
    }

    public function consumeForgotPassword(string $clientIp, string $email): void
    {
        $ipKey = $clientIp !== '' ? $clientIp : 'unknown';
        $emailKey = hash('sha256', mb_strtolower(trim($email)));

        $ipLimit = $this->forgotPasswordIpLimiter->create($ipKey)->consume(1);
        $emailLimit = $this->forgotPasswordEmailLimiter->create($emailKey)->consume(1);

        if (!$ipLimit->isAccepted() || !$emailLimit->isAccepted()) {
            throw new TooManyAuthRequestsException();
        }
    }

    public function consumeResendVerification(string $clientIp, int $userId): void
    {
        $ipKey = $clientIp !== '' ? $clientIp : 'unknown';
        $limit = $this->resendVerificationLimiter->create($ipKey.':'.$userId)->consume(1);
        if (!$limit->isAccepted()) {
            throw new TooManyAuthRequestsException();
        }
    }
}
