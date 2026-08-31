<?php

declare(strict_types=1);

namespace App\Controller\Shooting;

use App\Dto\Request\CompleteShootingSessionRequest;
use App\Dto\Request\UpdateShootingSessionContextRequest;
use App\Enum\ShootingContextNature;
use App\Http\StatsDateRangeResolver;
use App\Security\ImpersonationResolver;
use App\Service\Auth\InvalidTokenException;
use App\Service\Shooting\InvalidShootingSessionStructureException;
use App\Service\Shooting\NoLinkedPlayerException;
use App\Service\Shooting\ShootingSessionAccessDeniedException;
use App\Service\Shooting\ShootingSessionAlreadyFinishedException;
use App\Service\Shooting\ShootingSessionNotFoundException;
use App\Service\Shooting\ShootingSessionService;
use App\Service\Shooting\ShootingStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ShootingSessionController extends AbstractController
{
    public function __construct(
        private ShootingSessionService $service,
        private ShootingStatsService $statsService,
        private ImpersonationResolver $impersonation,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/shooting-sessions', name: 'api_shooting_sessions_start', methods: ['POST'])]
    public function start(Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) {
            $res = $this->service->start($token);
            return new JsonResponse($this->serializer->serialize($res, 'json'), 201, [], true);
        });
    }

    #[Route('/api/shooting-sessions/current', name: 'api_shooting_sessions_current', methods: ['GET'])]
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

    #[Route('/api/shooting-sessions/stats', name: 'api_shooting_sessions_stats', methods: ['GET'])]
    public function stats(Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) use ($request) {
            try {
                $dateRange = StatsDateRangeResolver::fromRequest($request);
            } catch (\InvalidArgumentException $e) {
                return new JsonResponse(['message' => $e->getMessage()], 400);
            }

            $natureParam = $request->query->get('nature');
            $contextNature = null;
            if ($natureParam !== null && $natureParam !== '' && $natureParam !== 'all') {
                $contextNature = ShootingContextNature::tryFrom((string) $natureParam);
                if ($contextNature === null) {
                    return new JsonResponse(['message' => 'Invalid nature filter.'], 400);
                }
            }

            $impersonatePlayerId = $this->impersonation->resolveOptionalFromToken($token, $request);
            $res = $this->statsService->stats($token, $contextNature, $dateRange, $impersonatePlayerId);
            return new JsonResponse($this->serializer->serialize($res, 'json'), 200, [], true);
        });
    }

    #[Route('/api/shooting-sessions/history', name: 'api_shooting_sessions_history', methods: ['GET'])]
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

    #[Route('/api/shooting-sessions/{id}', name: 'api_shooting_sessions_get', methods: ['GET'])]
    public function getOne(int $id, Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) use ($id, $request) {
            try {
                $impersonatePlayerId = $this->impersonation->resolveOptionalFromToken($token, $request);
                $res = $this->service->getSummary($token, $id, $impersonatePlayerId);
                return new JsonResponse($this->serializer->serialize($res, 'json'), 200, [], true);
            } catch (ShootingSessionNotFoundException) {
                return new JsonResponse(['message' => 'Not found'], 404);
            } catch (ShootingSessionAccessDeniedException) {
                return new JsonResponse(['message' => 'Forbidden'], 403);
            }
        });
    }

    #[Route('/api/shooting-sessions/{id}/complete', name: 'api_shooting_sessions_complete', methods: ['POST'])]
    public function complete(int $id, Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) use ($id, $request) {
            /** @var CompleteShootingSessionRequest $input */
            $input = $this->serializer->deserialize($request->getContent(), CompleteShootingSessionRequest::class, 'json');
            $violations = $this->validator->validate($input);
            if (\count($violations) > 0) {
                $errors = [];
                foreach ($violations as $v) {
                    $errors[$v->getPropertyPath()] = $v->getMessage();
                }
                return new JsonResponse(['errors' => $errors], 400);
            }

            try {
                $res = $this->service->complete($token, $id, $input);
                return new JsonResponse($this->serializer->serialize($res, 'json'), 200, [], true);
            } catch (ShootingSessionNotFoundException) {
                return new JsonResponse(['message' => 'Not found'], 404);
            } catch (ShootingSessionAccessDeniedException) {
                return new JsonResponse(['message' => 'Forbidden'], 403);
            } catch (ShootingSessionAlreadyFinishedException) {
                return new JsonResponse(['message' => 'Session already finished.'], 409);
            } catch (InvalidShootingSessionStructureException $e) {
                return new JsonResponse(['errors' => $e->errors], 400);
            }
        });
    }

    #[Route('/api/shooting-sessions/{id}/context', name: 'api_shooting_sessions_update_context', methods: ['PUT'])]
    public function updateContext(int $id, Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) use ($id, $request) {
            /** @var UpdateShootingSessionContextRequest $input */
            $input = $this->serializer->deserialize($request->getContent(), UpdateShootingSessionContextRequest::class, 'json');
            $violations = $this->validator->validate($input);
            if (\count($violations) > 0) {
                $errors = [];
                foreach ($violations as $v) {
                    $errors[$v->getPropertyPath()] = $v->getMessage();
                }
                return new JsonResponse(['errors' => $errors], 400);
            }

            try {
                $res = $this->service->updateContext($token, $id, $input);
                return new JsonResponse($this->serializer->serialize($res, 'json'), 200, [], true);
            } catch (ShootingSessionNotFoundException) {
                return new JsonResponse(['message' => 'Not found'], 404);
            } catch (ShootingSessionAccessDeniedException) {
                return new JsonResponse(['message' => 'Forbidden'], 403);
            }
        });
    }

    #[Route('/api/shooting-sessions/{id}', name: 'api_shooting_sessions_abandon', methods: ['DELETE'])]
    public function abandon(int $id, Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) use ($id) {
            try {
                $this->service->abandon($token, $id);
                return new JsonResponse(null, 204);
            } catch (ShootingSessionNotFoundException) {
                return new JsonResponse(['message' => 'Not found'], 404);
            } catch (ShootingSessionAccessDeniedException) {
                return new JsonResponse(['message' => 'Forbidden'], 403);
            } catch (ShootingSessionAlreadyFinishedException) {
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
}
