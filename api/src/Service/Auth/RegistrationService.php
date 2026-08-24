<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Dto\Auth\RegisterInput;
use App\Dto\Response\AuthSessionResponse;
use App\Dto\Response\MeResponse;
use App\Entity\User;
use App\Entity\Player;
use App\Repository\UserRepository;
use App\Service\Account\PlayerAlreadyLinkedException;
use App\Service\Account\PlayerLinkService;
use App\Service\Account\PlayerNotFoundException;
use App\Service\Account\UserAlreadyHasPlayerException;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

final class RegistrationService
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private PlayerLinkService $playerLinkService,
        private JWTEncoderInterface $jwtEncoder,
        private EmailVerificationService $emailVerificationService,
        #[Autowire(param: 'lexik_jwt_authentication.token_ttl')]
        private int $tokenTtl,
    ) {
    }

    /**
     * @throws RegistrationValidationException when input is invalid
     * @throws EmailAlreadyUsedException when email already exists
     * @throws PlayerNotFoundException when selected player does not exist
     * @throws PlayerAlreadyLinkedException when selected player is already linked
     */
    public function register(RegisterInput $input): AuthSessionResponse
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

        $existing = $this->userRepository->findOneBy(['email' => $input->email]);
        if ($existing !== null) {
            throw new EmailAlreadyUsedException();
        }

        $user = new User($input->email);
        $hashed = $this->passwordHasher->hashPassword($user, $input->password);
        $user->setPassword($hashed);

        $this->em->persist($user);
        $this->em->flush();

        if ($input->playerId !== null) {
            $player = $this->playerLinkService->linkToUser($user, $input->playerId);
        } else {
            $firstName = trim((string) $input->firstName);
            $lastName = trim((string) $input->lastName);
            $nickname = $input->nickname !== null && trim($input->nickname) !== ''
                ? trim($input->nickname)
                : $firstName;

            $player = new Player(
                firstName: $firstName,
                lastName: $lastName,
                nickname: $nickname,
            );
            $player->setUser($user);

            $this->em->persist($player);
            $this->em->flush();
        }

        $this->emailVerificationService->sendForUser($user);

        $token = $this->jwtEncoder->encode([
            'username' => $user->getEmail(),
            'sub' => (string) $user->getId(),
            'roles' => [],
            'exp' => time() + $this->tokenTtl,
            'iat' => time(),
        ]);

        return new AuthSessionResponse(
            token: $token,
            user: new MeResponse(
                id: (int) $user->getId(),
                email: $user->getEmail(),
                playerId: (int) $player->getId(),
                firstName: $player->getFirstName(),
                lastName: $player->getLastName(),
                nickname: $player->getNickname(),
                emailVerified: $user->isEmailVerified(),
            ),
        );
    }
}

final class EmailAlreadyUsedException extends \RuntimeException {}

final class RegistrationValidationException extends \RuntimeException
{
    /** @var array<int, array{field: string, message: string}> */
    public array $errors;

    /** @param array<int, array{field: string, message: string}> $errors */
    public function __construct(array $errors)
    {
        parent::__construct('Invalid input');
        $this->errors = $errors;
    }
}
