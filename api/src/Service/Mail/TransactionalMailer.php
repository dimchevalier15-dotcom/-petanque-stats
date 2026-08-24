<?php

declare(strict_types=1);

namespace App\Service\Mail;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

final class TransactionalMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private LoggerInterface $logger,
        #[Autowire(param: 'app.mail_from')]
        private string $mailFrom,
        #[Autowire(param: 'app.mail_from_name')]
        private string $mailFromName,
        #[Autowire(param: 'app.api_base_url')]
        private string $apiBaseUrl,
        #[Autowire(param: 'app.frontend_base_url')]
        private string $frontendBaseUrl,
    ) {
    }

    public function sendEmailVerification(User $user, string $plainToken): void
    {
        $verifyUrl = rtrim($this->apiBaseUrl, '/').'/api/auth/verify-email?token='.rawurlencode($plainToken);
        $this->send(
            $user,
            'Confirmez votre adresse e-mail — Pétanque Analytics',
            'emails/verify_email.html.twig',
            'emails/verify_email.txt.twig',
            [
                'verifyUrl' => $verifyUrl,
                'ttlHours' => 24,
            ],
        );
    }

    public function sendPasswordReset(User $user, string $plainToken): void
    {
        $resetUrl = rtrim($this->frontendBaseUrl, '/').'/reset-password?token='.rawurlencode($plainToken);
        $this->send(
            $user,
            'Réinitialisez votre mot de passe — Pétanque Analytics',
            'emails/reset_password.html.twig',
            'emails/reset_password.txt.twig',
            [
                'resetUrl' => $resetUrl,
                'ttlHours' => 1,
            ],
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    private function send(User $user, string $subject, string $htmlTemplate, string $textTemplate, array $context): void
    {
        $email = (new Email())
            ->from(new Address($this->mailFrom, $this->mailFromName))
            ->to($user->getEmail())
            ->subject($subject)
            ->html($this->twig->render($htmlTemplate, $context))
            ->text($this->twig->render($textTemplate, $context));

        try {
            $this->mailer->send($email);
        } catch (\Throwable $e) {
            $this->logger->error('Transactional email sending failed', [
                'user_id' => $user->getId(),
                'subject' => $subject,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
