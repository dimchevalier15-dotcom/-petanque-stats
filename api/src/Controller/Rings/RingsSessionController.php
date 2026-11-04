<?php

declare(strict_types=1);

namespace App\Controller\Rings;

use App\Dto\Request\CompleteRingsSessionRequest;
use App\Dto\Request\StartRingsSessionRequest;
use App\Enum\RingsMode;
use App\Http\StatsDateRangeResolver;
use App\Security\ImpersonationResolver;
use App\Service\Auth\InvalidTokenException;
use App\Service\Rings\InvalidRingsSessionStructureException;
use App\Service\Rings\NoLinkedPlayerException;
use App\Service\Rings\RingsSessionAccessDeniedException;
use App\Service\Rings\RingsSessionAlreadyFinishedException;
use App\Service\Rings\RingsSessionNotFoundException;
use App\Service\Rings\RingsSessionService;
use App\Service\Rings\RingsStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RingsSessionController extends AbstractController
{
    public function __construct(
        private RingsSessionService $service,
        private RingsStatsService $statsService,
        private ImpersonationResolver $impersonation,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/rings-sessions', name: 'api_rings_sessions_start', methods: ['POST'])]
    public function start(Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) use ($request) {
            $content = trim((string) $request->getContent());
            $input = null;
            if ($content !== '') {
                /** @var StartRingsSessionRequest $input */
                $input = $this->serializer->deserialize($content, StartRingsSessionRequest::class, 'json');
                $violations = $this->validator->validate($input);
                if (\count($violations) > 0) {
                    return $this->validationError($violations);
                }
            }

            $res = $this->service->start($token, $input);

            return new JsonResponse($this->serializer->serialize($res, 'json'), 201, [], true);
        });
    }

    #[Route('/api/rings-sessions/current', name: 'api_rings_sessions_current', methods: ['GET'])]
    public function current(Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) use ($request) {
            $mode = $this->resolveMode($request);
            if ($mode === null) {
                return $this->invalidModeError();
            }

            $res = $this->service->current($token, $mode);
            if ($res === null) {
                return new JsonResponse('null', 200, [], true);
            }

            return new JsonResponse($this->serializer->serialize($res, 'json'), 200, [], true);
        });
    }

    #[Route('/api/rings-sessions/history', name: 'api_rings_sessions_history', methods: ['GET'])]
    public function history(Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) use ($request) {
            $mode = $this->resolveMode($request);
            if ($mode === null) {
                return $this->invalidModeError();
            }

            $page = $request->query->get('page') !== null ? max(1, (int) $request->query->get('page')) : 1;
            $size = $request->query->get('size') !== null ? max(1, (int) $request->query->get('size')) : 20;
            $impersonatePlayerId = $this->impersonation->resolveOptionalFromToken($token, $request);
            $res = $this->service->history($token, $page, $size, $impersonatePlayerId, $mode);

            return new JsonResponse($this->serializer->serialize($res, 'json'), 200, [], true);
        });
    }

    #[Route('/api/rings-sessions/stats', name: 'api_rings_sessions_stats', methods: ['GET'])]
    public function stats(Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) use ($request) {
            try {
                $dateRange = StatsDateRangeResolver::fromRequest($request);
            } catch (\InvalidArgumentException $e) {
                return new JsonResponse(['message' => $e->getMessage()], 400);
            }

            $mode = $this->resolveMode($request);
            if ($mode === null) {
                return $this->invalidModeError();
            }

            $impersonatePlayerId = $this->impersonation->resolveOptionalFromToken($token, $request);
            $res = $this->statsService->stats($token, $dateRange, $impersonatePlayerId, $mode);

            return new JsonResponse($this->serializer->serialize($res, 'json'), 200, [], true);
        });
    }

    #[Route('/api/rings-sessions/{id}', name: 'api_rings_sessions_get', methods: ['GET'])]
    public function getOne(int $id, Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) use ($id, $request) {
            try {
                $impersonatePlayerId = $this->impersonation->resolveOptionalFromToken($token, $request);
                $res = $this->service->getSummary($token, $id, $impersonatePlayerId);

                return new JsonResponse($this->serializer->serialize($res, 'json'), 200, [], true);
            } catch (RingsSessionNotFoundException) {
                return new JsonResponse(['message' => 'Not found'], 404);
            } catch (RingsSessionAccessDeniedException) {
                return new JsonResponse(['message' => 'Forbidden'], 403);
            }
        });
    }

    #[Route('/api/rings-sessions/{id}/complete', name: 'api_rings_sessions_complete', methods: ['POST'])]
    public function complete(int $id, Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) use ($id, $request) {
            /** @var CompleteRingsSessionRequest $input */
            $input = $this->serializer->deserialize($request->getContent(), CompleteRingsSessionRequest::class, 'json');
            $violations = $this->validator->validate($input);
            if (\count($violations) > 0) {
                return $this->validationError($violations);
            }

            try {
                $res = $this->service->complete($token, $id, $input);

                return new JsonResponse($this->serializer->serialize($res, 'json'), 200, [], true);
            } catch (RingsSessionNotFoundException) {
                return new JsonResponse(['message' => 'Not found'], 404);
            } catch (RingsSessionAccessDeniedException) {
                return new JsonResponse(['message' => 'Forbidden'], 403);
            } catch (RingsSessionAlreadyFinishedException) {
                return new JsonResponse(['message' => 'Session already finished.'], 409);
            } catch (InvalidRingsSessionStructureException $e) {
                return new JsonResponse(['errors' => $e->errors], 400);
            }
        });
    }

    #[Route('/api/rings-sessions/{id}', name: 'api_rings_sessions_abandon', methods: ['DELETE'])]
    public function abandon(int $id, Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) use ($id) {
            try {
                $this->service->abandon($token, $id);

                return new JsonResponse(null, 204);
            } catch (RingsSessionNotFoundException) {
                return new JsonResponse(['message' => 'Not found'], 404);
            } catch (RingsSessionAccessDeniedException) {
                return new JsonResponse(['message' => 'Forbidden'], 403);
            } catch (RingsSessionAlreadyFinishedException) {
                return new JsonResponse(['message' => 'Session already finished.'], 409);
            }
        });
    }

    private function resolveMode(Request $request): ?RingsMode
    {
        $raw = $request->query->get('mode');

        return $raw === null || $raw === '' ? RingsMode::POINT : RingsMode::tryFrom((string) $raw);
    }

    private function invalidModeError(): JsonResponse
    {
        return new JsonResponse(['errors' => ['mode' => 'Invalid mode.']], 400);
    }

    private function validationError(ConstraintViolationListInterface $violations): JsonResponse
    {
        $errors = [];
        foreach ($violations as $v) {
            $errors[$v->getPropertyPath()] = $v->getMessage();
        }

        return new JsonResponse(['errors' => $errors], 400);
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
