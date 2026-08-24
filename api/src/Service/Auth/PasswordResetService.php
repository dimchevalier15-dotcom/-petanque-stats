<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Dto\Request\ResetPasswordRequest;
use App\Enum\AuthTokenPurpose;
use App\Repository\UserRepository;
use App\Service\Mail\TransactionalMailer;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class PasswordResetService
{
    public const GENERIC_REQUEST_MESSAGE = 'If an account exists for this email, a password reset email has been sent.';

    public function __construct(
        private UserRepository $users,
        private AuthTokenIssuer $tokens,
        private TransactionalMailer $mailer,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
        private AuthRateLimiter $rateLimiter,
    ) {
    }

    public function requestReset(string $email, string $clientIp): void
    {
        $this->rateLimiter->consumeForgotPassword($clientIp, $email);

        $user = $this->users->findOneByEmail(trim($email));
        if ($user === null) {
            return;
        }

        $plainToken = $this->tokens->issue(
            $user,
            AuthTokenPurpose::PasswordReset,
            new DateInterval('PT1H'),
        );
        $this->mailer->sendPasswordReset($user, $plainToken);
    }

    /**
     * @throws InvalidAuthTokenException
     * @throws RegistrationValidationException
     */
    public function resetPassword(ResetPasswordRequest $input): void
    {
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            $errors = [];
            /** @var ConstraintViolationInterface $violation */
            foreach ($violations as $violation) {
                $errors[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }
            throw new RegistrationValidationException($errors);
        }

        $token = $this->tokens->consume($input->token, AuthTokenPurpose::PasswordReset);
        $user = $token->getUser();
        $user->setPassword($this->passwordHasher->hashPassword($user, $input->password));
        $this->em->flush();
    }
}
