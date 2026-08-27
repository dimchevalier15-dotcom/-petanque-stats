<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Dto\Response\MeResponse;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\MeResponseFactory;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

final class CurrentUserService
{
    public function __construct(
        private JWTEncoderInterface $jwtEncoder,
        private UserRepository $users,
        private MeResponseFactory $meResponseFactory,
    ) {
    }

    /**
     * @throws InvalidTokenException
     */
    public function meFromToken(string $token): MeResponse
    {
        $user = $this->getUserFromToken($token);

        return $this->meResponseFactory->fromUser($user);
    }

    /**
     * @throws InvalidTokenException
     */
    public function getUserFromToken(string $token): User
    {
        try {
            /** @var array{username?: string, sub?: string} $payload */
            $payload = $this->jwtEncoder->decode($token);
        } catch (\Throwable) {
            throw new InvalidTokenException();
        }

        $userId = isset($payload['sub']) ? (int) $payload['sub'] : null;
        $email = isset($payload['username']) ? (string) $payload['username'] : null;
        if ($userId === null && $email === null) {
            throw new InvalidTokenException();
        }

        $user = $userId !== null ? $this->users->find($userId) : $this->users->findOneByEmail((string) $email);
        if ($user === null) {
            throw new InvalidTokenException();
        }

        return $user;
    }
}

final class InvalidTokenException extends \RuntimeException {}
