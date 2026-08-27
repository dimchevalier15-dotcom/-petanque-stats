<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Repository\PlayerRepository;
use App\Service\Auth\CurrentUserService;
use App\Service\Auth\InvalidTokenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ImpersonationResolver
{
    public const HEADER = 'X-Impersonate-Player-Id';

    public function __construct(
        private CurrentUserService $currentUser,
        private PlayerRepository $players,
    ) {
    }

    /**
     * @throws InvalidTokenException
     */
    public function resolveOptionalFromToken(string $token, Request $request): ?int
    {
        $user = $this->currentUser->getUserFromToken($token);

        return $this->resolveOptionalForUser($request, $user);
    }

    public function resolveOptionalForUser(Request $request, User $user): ?int
    {
        $header = $request->headers->get(self::HEADER);
        if ($header === null || $header === '') {
            return null;
        }

        if (!$user->isMaster()) {
            throw new AccessDeniedHttpException('Impersonation is not allowed.');
        }

        if (!is_numeric($header) || (int) $header <= 0) {
            throw new BadRequestHttpException('Invalid impersonation player id.');
        }

        $playerId = (int) $header;
        $player = $this->players->find($playerId);
        if ($player === null) {
            throw new NotFoundHttpException('Impersonated player not found.');
        }

        return $playerId;
    }
}
