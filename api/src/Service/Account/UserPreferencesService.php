<?php

declare(strict_types=1);

namespace App\Service\Account;

use App\Dto\Request\UpdateUserPreferencesRequest;
use App\Dto\Response\UserPreferencesResponse;
use App\Repository\UserRepository;
use App\Service\Auth\CurrentUserService;
use App\Service\Auth\InvalidTokenException;
use Doctrine\ORM\EntityManagerInterface;

final class UserPreferencesService
{
    public function __construct(
        private CurrentUserService $currentUser,
        private UserRepository $users,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @throws InvalidTokenException
     */
    public function getPreferences(string $token): UserPreferencesResponse
    {
        $user = $this->currentUser->getUserFromToken($token);

        return new UserPreferencesResponse($user->requiresMatchValidation());
    }

    /**
     * @throws InvalidTokenException
     */
    public function updatePreferences(string $token, UpdateUserPreferencesRequest $request): UserPreferencesResponse
    {
        $user = $this->currentUser->getUserFromToken($token);
        $user->setRequiresMatchValidation($request->requiresMatchValidation);
        $this->em->flush();

        return new UserPreferencesResponse($user->requiresMatchValidation());
    }
}
