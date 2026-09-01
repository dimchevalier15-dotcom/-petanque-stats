<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Request\CompleteMatchRequest;
use App\Dto\Request\CreateMatchRequest;
use App\Dto\Request\UpdateMatchContextRequest;
use App\Dto\Request\UpdateMatchValidationRequest;
use App\Entity\Game;
use App\Entity\User;
use App\Security\Voter\GameVoter;
use App\Security\ImpersonationResolver;
use App\Service\MatchContextService;
use App\Service\MatchHistoryService;
use App\Service\MatchRecordingService;
use App\Service\MatchService;
use App\Service\MatchSummaryService;
use App\Service\MatchValidationException;
use App\Service\MatchValidationOwnershipException;
use App\Service\MatchShareService;
use App\Service\MatchValidationService;
use App\Service\PlayerViewContextResolver;
use App\Service\Auth\InvalidTokenException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class MatchController extends AbstractController
{
    public function __construct(
        private MatchService $service,
        private MatchRecordingService $recording,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private MatchSummaryService $summary,
        private MatchHistoryService $history,
        private MatchContextService $context,
        private ImpersonationResolver $impersonation,
        private MatchValidationService $validation,
        private PlayerViewContextResolver $playerViewContext,
        private MatchShareService $share,
    ) {}

    #[Route('/api/matches', name: 'api_matches_create', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        try {
            /** @var CreateMatchRequest $input */
            $input = $this->serializer->deserialize($request->getContent(), CreateMatchRequest::class, 'json');
            $violations = $this->validator->validate($input);
            if (\count($violations) > 0) {
                $errors = [];
                foreach ($violations as $v) {
                    $field = $v->getPropertyPath();
                    $errors[$field] = $v->getMessage();
                }

                return new JsonResponse(['errors' => $errors], 400);
            }

            $res = $this->service->create($input, $user);
            $json = $this->serializer->serialize($res, 'json');

            return new JsonResponse($json, 201, [], true);
        } catch (MatchValidationException $e) {
            return new JsonResponse(['errors' => $e->errors], 400);
        }
    }

    #[Route('/api/matches/{id}/complete', name: 'api_matches_complete', methods: ['POST'])]
    #[IsGranted(GameVoter::EDIT, subject: 'game')]
    public function complete(#[MapEntity] Game $game, Request $request): JsonResponse
    {
        /** @var CompleteMatchRequest $input */
        $input = $this->serializer->deserialize($request->getContent(), CompleteMatchRequest::class, 'json');
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $field = $v->getPropertyPath();
                $errors[$field] = $v->getMessage();
            }

            return new JsonResponse(['errors' => $errors], 400);
        }

        $res = $this->recording->complete((int) $game->getId(), $input);
        $json = $this->serializer->serialize($res, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/matches/{id}/summary', name: 'api_matches_summary', methods: ['GET'])]
    #[IsGranted(GameVoter::VIEW, subject: 'game')]
    public function summary(#[MapEntity] Game $game, Request $request): JsonResponse
    {
        $this->share->ensureShareUuid($game);
        $viewerPlayerId = $this->resolveViewerPlayerId($request);
        $res = $this->summary->getSummary((int) $game->getId(), $viewerPlayerId);
        if ($res === null) {
            return new JsonResponse(['message' => 'Not found'], 404);
        }
        $json = $this->serializer->serialize($res, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/matches/{id}/context', name: 'api_matches_context_get', methods: ['GET'])]
    #[IsGranted(GameVoter::VIEW, subject: 'game')]
    public function getContext(#[MapEntity] Game $game): JsonResponse
    {
        $res = $this->context->getContext((int) $game->getId());
        if ($res === null) {
            return new JsonResponse(['message' => 'Not found'], 404);
        }
        $json = $this->serializer->serialize($res, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/matches/{id}/context', name: 'api_matches_context_update', methods: ['PUT'])]
    #[IsGranted(GameVoter::EDIT, subject: 'game')]
    public function updateContext(#[MapEntity] Game $game, Request $request): JsonResponse
    {
        /** @var UpdateMatchContextRequest $input */
        $input = $this->serializer->deserialize($request->getContent(), UpdateMatchContextRequest::class, 'json');
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $field = $v->getPropertyPath();
                $errors[$field] = $v->getMessage();
            }

            return new JsonResponse(['errors' => $errors], 400);
        }

        $res = $this->context->updateContext((int) $game->getId(), $input);
        if ($res === null) {
            return new JsonResponse(['message' => 'Not found'], 404);
        }
        $json = $this->serializer->serialize($res, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/matches/history', name: 'api_matches_history', methods: ['GET'])]
    public function history(Request $request): JsonResponse
    {
        $authHeader = (string) $request->headers->get('Authorization', '');
        $token = substr($authHeader, 7);
        $page = $request->query->get('page') !== null ? max(1, (int) $request->query->get('page')) : 1;
        $size = $request->query->get('size') !== null ? max(1, (int) $request->query->get('size')) : 20;

        try {
            $impersonatePlayerId = $this->impersonation->resolveOptionalFromToken($token, $request);
            $res = $this->history->historyForToken($token, $page, $size, $impersonatePlayerId);
        } catch (InvalidTokenException) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        }
        $json = $this->serializer->serialize($res, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/matches/pending-validation', name: 'api_matches_pending_validation', methods: ['GET'])]
    public function pendingValidation(Request $request): JsonResponse
    {
        $token = $this->extractToken($request);
        if ($token === null) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        }

        try {
            $impersonatePlayerId = $this->impersonation->resolveOptionalFromToken($token, $request);
            $res = $this->validation->pendingForToken($token, $impersonatePlayerId);
        } catch (InvalidTokenException) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        }
        $json = $this->serializer->serialize($res, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/matches/pending-validation/count', name: 'api_matches_pending_validation_count', methods: ['GET'])]
    public function pendingValidationCount(Request $request): JsonResponse
    {
        $token = $this->extractToken($request);
        if ($token === null) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        }

        try {
            $impersonatePlayerId = $this->impersonation->resolveOptionalFromToken($token, $request);
            $res = $this->validation->countPendingForToken($token, $impersonatePlayerId);
        } catch (InvalidTokenException) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        }
        $json = $this->serializer->serialize($res, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/match-players/{id}/validation', name: 'api_match_players_validation', methods: ['PUT'])]
    public function updateValidation(int $id, Request $request): JsonResponse
    {
        $token = $this->extractToken($request);
        if ($token === null) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        }

        /** @var UpdateMatchValidationRequest $input */
        $input = $this->serializer->deserialize($request->getContent(), UpdateMatchValidationRequest::class, 'json');
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0 || $input->validated === null) {
            return new JsonResponse(['error' => 'invalid_request'], 400);
        }

        try {
            $impersonatePlayerId = $this->impersonation->resolveOptionalFromToken($token, $request);
            $this->validation->updateValidation($token, $id, $input->validated, $impersonatePlayerId);
        } catch (InvalidTokenException) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        } catch (MatchValidationOwnershipException) {
            return new JsonResponse(['error' => 'forbidden'], 403);
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

    private function resolveViewerPlayerId(Request $request): ?int
    {
        $token = $this->extractToken($request);
        if ($token === null) {
            return null;
        }

        try {
            $impersonatePlayerId = $this->impersonation->resolveOptionalFromToken($token, $request);
            $context = $this->playerViewContext->resolve($token, $impersonatePlayerId);

            return $context->playerId;
        } catch (InvalidTokenException) {
            return null;
        }
    }
}
