<?php

declare(strict_types=1);

namespace App\Controller\Account;

use App\Dto\Request\LinkPlayerRequest;
use App\Dto\Request\UpdatePlayerProfileRequest;
use App\Service\Account\AccountDeletionService;
use App\Service\Account\AccountPlayerService;
use App\Service\Account\NoLinkedPlayerException;
use App\Service\Account\PlayerAlreadyLinkedException;
use App\Service\Account\PlayerNotFoundException;
use App\Service\Account\UserAlreadyHasPlayerException;
use App\Service\Auth\InvalidTokenException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AccountController extends AbstractController
{
    public function __construct(
        private AccountPlayerService $accountPlayerService,
        private AccountDeletionService $accountDeletionService,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/account/player', name: 'api_account_player_get', methods: ['GET'])]
    public function getLinkedPlayer(Request $request): JsonResponse
    {
        $token = $this->extractToken($request);
        if ($token === null) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        }

        try {
            $player = $this->accountPlayerService->getLinkedPlayer($token);
            if ($player === null) {
                return new JsonResponse('null', 200, [], true);
            }
            $json = $this->serializer->serialize($player, 'json');

            return new JsonResponse($json, 200, [], true);
        } catch (InvalidTokenException) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        }
    }

    #[Route('/api/account/players/search', name: 'api_account_players_search', methods: ['GET'])]
    public function searchUnlinkedPlayers(Request $request): JsonResponse
    {
        $token = $this->extractToken($request);
        if ($token === null) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        }

        try {
            $q = $request->query->get('q');
            $items = $this->accountPlayerService->searchUnlinkedPlayers($token, $q !== null ? (string) $q : null);
            $json = $this->serializer->serialize($items, 'json');

            return new JsonResponse($json, 200, [], true);
        } catch (InvalidTokenException) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        }
    }

    #[Route('/api/account/player/link', name: 'api_account_player_link', methods: ['POST'])]
    public function linkPlayer(Request $request): JsonResponse
    {
        $token = $this->extractToken($request);
        if ($token === null) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        }

        try {
            /** @var LinkPlayerRequest $input */
            $input = $this->serializer->deserialize($request->getContent(), LinkPlayerRequest::class, 'json');
            $violations = $this->validator->validate($input);
            if (\count($violations) > 0) {
                return new JsonResponse(['error' => 'invalid_request'], 400);
            }

            $player = $this->accountPlayerService->linkPlayer($token, $input);
            $json = $this->serializer->serialize($player, 'json');

            return new JsonResponse($json, 200, [], true);
        } catch (InvalidTokenException) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        } catch (UserAlreadyHasPlayerException) {
            return new JsonResponse(['error' => 'user_already_has_player'], 409);
        } catch (PlayerNotFoundException) {
            return new JsonResponse(['error' => 'player_not_found'], 404);
        } catch (PlayerAlreadyLinkedException) {
            return new JsonResponse(['error' => 'player_already_linked'], 409);
        }
    }

    #[Route('/api/account/player', name: 'api_account_player_update', methods: ['PUT'])]
    public function updateProfile(Request $request): JsonResponse
    {
        $token = $this->extractToken($request);
        if ($token === null) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        }

        try {
            /** @var UpdatePlayerProfileRequest $input */
            $input = $this->serializer->deserialize($request->getContent(), UpdatePlayerProfileRequest::class, 'json');
            $violations = $this->validator->validate($input);
            if (\count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $field = $violation->getPropertyPath();
                    $errors[$field] = $violation->getMessage();
                }

                return new JsonResponse(['errors' => $errors], 400);
            }

            $player = $this->accountPlayerService->updateProfile($token, $input);
            $json = $this->serializer->serialize($player, 'json');

            return new JsonResponse($json, 200, [], true);
        } catch (InvalidTokenException) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        } catch (NoLinkedPlayerException) {
            return new JsonResponse(['error' => 'no_linked_player'], 409);
        } catch (PlayerNotFoundException) {
            return new JsonResponse(['error' => 'player_not_found'], 404);
        }
    }

    #[Route('/api/account', name: 'api_account_delete', methods: ['DELETE'])]
    public function deleteAccount(Request $request): JsonResponse
    {
        $token = $this->extractToken($request);
        if ($token === null) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        }

        try {
            $this->accountDeletionService->deleteAccount($token);
        } catch (InvalidTokenException) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        }

        return new JsonResponse(null, 204);
    }

    private function extractToken(Request $request): ?string
    {
        $authHeader = (string) $request->headers->get('Authorization', '');
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        return substr($authHeader, 7);
    }
}
