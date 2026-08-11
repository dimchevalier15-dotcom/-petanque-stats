<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Dto\Request\LoginRequest;
use App\Dto\Response\LoginResponse;
use App\Repository\UserRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

final class LoginService
{
    public function __construct(
        private UserRepository $users,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTEncoderInterface $jwtEncoder,
        #[Autowire(param: 'lexik_jwt_authentication.token_ttl')]
        private int $tokenTtl,
    ) {}

    /**
     * @throws InvalidCredentialsException
     */
    public function login(LoginRequest $req): LoginResponse
    {
        $user = $this->users->findOneByEmail($req->email);
        if ($user === null) {
            throw new InvalidCredentialsException();
        }
        if (!$this->passwordHasher->isPasswordValid($user, $req->password)) {
            throw new InvalidCredentialsException();
        }

        $token = $this->jwtEncoder->encode([
            'username' => $user->getEmail(),
            'sub' => (string) $user->getId(),
            'roles' => [],
            'exp' => time() + $this->tokenTtl,
            'iat' => time(),
        ]);

        return new LoginResponse($token);
    }
}

final class InvalidCredentialsException extends \RuntimeException {}
