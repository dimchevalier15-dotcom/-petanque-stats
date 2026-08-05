<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Dto\Response\MeResponse;
use App\Repository\PlayerRepository;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

final class CurrentUserService
{
    public function __construct(
        private JWTEncoderInterface $jwtEncoder,
        private UserRepository $users,
        private PlayerRepository $players,
    ) {
    }

    /**
     * @throws InvalidTokenException
     */
    public function meFromToken(string $token): MeResponse
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

        $player = $this->players->findOneByUserId((int) $user->getId());

        return new MeResponse(
            id: (int) $user->getId(),
            email: $user->getEmail(),
            playerId: $player?->getId() !== null ? (int) $player->getId() : null,
            firstName: $player?->getFirstName(),
            lastName: $player?->getLastName(),
            nickname: $player?->getNickname(),
        );
    }
}

final class InvalidTokenException extends \RuntimeException {}
