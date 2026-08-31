<?php

declare(strict_types=1);

namespace App\Controller\Training;

use App\Dto\Request\CreateTrainingSessionRequest;
use App\Dto\Request\RecordTrainingAttemptRequest;
use App\Enum\TrainingType;
use App\Http\StatsDateRangeResolver;
use App\Security\ImpersonationResolver;
use App\Service\Auth\InvalidTokenException;
use App\Service\Training\InvalidTrainingAttemptException;
use App\Service\Training\NoLinkedPlayerException;
use App\Service\Training\TrainingSessionAccessDeniedException;
use App\Service\Training\TrainingSessionAlreadyFinishedException;
use App\Service\Training\TrainingSessionNotFoundException;
use App\Service\Training\TrainingSessionService;
use App\Service\Training\TrainingStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class TrainingSessionController extends AbstractController
{
    public function __construct(
        private TrainingSessionService $service,
        private TrainingStatsService $statsService,
        private ImpersonationResolver $impersonation,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/training-sessions', name: 'api_training_sessions_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) use ($request) {
            /** @var CreateTrainingSessionRequest $input */
            $input = $this->serializer->deserialize($request->getContent(), CreateTrainingSessionRequest::class, 'json');
            $violations = $this->validator->validate($input);
            if (\count($violations) > 0) {
                return $this->validationError($violations);
            }

            $res = $this->service->create($token, $input);
            return new JsonResponse($this->serializer->serialize($res, 'json'), 201, [], true);
        });
    }

    #[Route('/api/training-sessions/current', name: 'api_training_sessions_current', methods: ['GET'])]
    public function current(Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) {
            $res = $this->service->current($token);
            if ($res === null) {
                return new JsonResponse('null', 200, [], true);
            }
            return new JsonResponse($this->serializer->serialize($res, 'json'), 200, [], true);
        });
    }

    #[Route('/api/training-sessions/stats', name: 'api_training_sessions_stats', methods: ['GET'])]
    public function stats(Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) use ($request) {
            try {
                $dateRange = StatsDateRangeResolver::fromRequest($request);
            } catch (\InvalidArgumentException $e) {
                return new JsonResponse(['message' => $e->getMessage()], 400);
            }

            $typeParam = $request->query->get('type');
            $type = null;
            if ($typeParam !== null && $typeParam !== '' && $typeParam !== 'all') {
                $type = TrainingType::tryFrom((string) $typeParam);
                if ($type === null) {
                    return new JsonResponse(['message' => 'Invalid type filter.'], 400);
                }
            }

            $impersonatePlayerId = $this->impersonation->resolveOptionalFromToken($token, $request);
            $res = $this->statsService->stats($token, $type, $dateRange, $impersonatePlayerId);
            return new JsonResponse($this->serializer->serialize($res, 'json'), 200, [], true);
        });
    }

    #[Route('/api/training-sessions/history', name: 'api_training_sessions_history', methods: ['GET'])]
    public function history(Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) use ($request) {
            $page = $request->query->get('page') !== null ? max(1, (int) $request->query->get('page')) : 1;
            $size = $request->query->get('size') !== null ? max(1, (int) $request->query->get('size')) : 20;
            $impersonatePlayerId = $this->impersonation->resolveOptionalFromToken($token, $request);
            $res = $this->service->history($token, $page, $size, $impersonatePlayerId);
            return new JsonResponse($this->serializer->serialize($res, 'json'), 200, [], true);
        });
    }

    #[Route('/api/training-sessions/{id}', name: 'api_training_sessions_get', methods: ['GET'])]
    public function getOne(int $id, Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) use ($id, $request) {
            try {
                $impersonatePlayerId = $this->impersonation->resolveOptionalFromToken($token, $request);
                $res = $this->service->getSummary($token, $id, $impersonatePlayerId);
                return new JsonResponse($this->serializer->serialize($res, 'json'), 200, [], true);
            } catch (TrainingSessionNotFoundException) {
                return new JsonResponse(['message' => 'Not found'], 404);
            } catch (TrainingSessionAccessDeniedException) {
                return new JsonResponse(['message' => 'Forbidden'], 403);
            }
        });
    }

    #[Route('/api/training-sessions/{id}/attempts', name: 'api_training_sessions_record_attempt', methods: ['POST'])]
    public function recordAttempt(int $id, Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) use ($id, $request) {
            /** @var RecordTrainingAttemptRequest $input */
            $input = $this->serializer->deserialize($request->getContent(), RecordTrainingAttemptRequest::class, 'json');
            $violations = $this->validator->validate($input);
            if (\count($violations) > 0) {
                return $this->validationError($violations);
            }

            try {
                $res = $this->service->recordAttempt($token, $id, $input);
                return new JsonResponse($this->serializer->serialize($res, 'json'), 200, [], true);
            } catch (TrainingSessionNotFoundException) {
                return new JsonResponse(['message' => 'Not found'], 404);
            } catch (TrainingSessionAccessDeniedException) {
                return new JsonResponse(['message' => 'Forbidden'], 403);
            } catch (TrainingSessionAlreadyFinishedException) {
                return new JsonResponse(['message' => 'Session already finished.'], 409);
            } catch (InvalidTrainingAttemptException $e) {
                return new JsonResponse(['errors' => $e->errors], 400);
            }
        });
    }

    #[Route('/api/training-sessions/{id}', name: 'api_training_sessions_abandon', methods: ['DELETE'])]
    public function abandon(int $id, Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) use ($id) {
            try {
                $this->service->abandon($token, $id);
                return new JsonResponse(null, 204);
            } catch (TrainingSessionNotFoundException) {
                return new JsonResponse(['message' => 'Not found'], 404);
            } catch (TrainingSessionAccessDeniedException) {
                return new JsonResponse(['message' => 'Forbidden'], 403);
            } catch (TrainingSessionAlreadyFinishedException) {
                return new JsonResponse(['message' => 'Session already finished.'], 409);
            }
        });
    }

    /**
     * @param callable(string): JsonResponse $action
     */
    private function withToken(Request $request, callable $action): JsonResponse
    {
        $authHeader = (string) $request->headers->get('Authorization', '');
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        }
        $token = substr($authHeader, 7);

        try {
            return $action($token);
        } catch (InvalidTokenException) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        } catch (NoLinkedPlayerException) {
            return new JsonResponse(['error' => 'no_linked_player'], 409);
        }
    }

    private function validationError(\Symfony\Component\Validator\ConstraintViolationListInterface $violations): JsonResponse
    {
        $errors = [];
        foreach ($violations as $v) {
            $errors[$v->getPropertyPath()] = $v->getMessage();
        }

        return new JsonResponse(['errors' => $errors], 400);
    }
}
