<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Dto\Auth\RegisterInput;
use App\Dto\Auth\RegisterOutput;
use App\Entity\User;
use App\Entity\Player;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

final class RegistrationService
{
    private UserRepository $userRepository;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $passwordHasher;
    private ValidatorInterface $validator;

    public function __construct(
        UserRepository $userRepository,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
    ) {
        $this->userRepository = $userRepository;
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
        $this->validator = $validator;
    }

    /**
     * @throws RegistrationValidationException when input is invalid
     * @throws EmailAlreadyUsedException when email already exists
     */
    public function register(RegisterInput $input): RegisterOutput
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

        // Create User and Player within a single unit of work
        $user = new User($input->email);
        $hashed = $this->passwordHasher->hashPassword($user, $input->password);
        $user->setPassword($hashed);

        // Derive default names from email local part
        $localPart = strtolower((string) strstr($input->email, '@', true));
        if ($localPart === '') {
            $localPart = strtolower($input->email);
        }

        $player = new Player(
            firstName: $localPart,
            lastName: $localPart,
            nickname: $localPart,
        );
        $player->setUser($user);

        $this->em->persist($user);
        $this->em->persist($player);
        $this->em->flush();

        return new RegisterOutput(
            id: (int) $user->getId(),
            email: $user->getEmail(),
            playerId: (int) $player->getId(),
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
