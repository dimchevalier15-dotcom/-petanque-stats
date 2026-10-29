<?php

declare(strict_types=1);

namespace App\Controller\JumpShot;

use App\Dto\Request\SubmitJumpShotSeriesRequest;
use App\Http\StatsDateRangeResolver;
use App\Security\ImpersonationResolver;
use App\Service\Auth\InvalidTokenException;
use App\Service\JumpShot\InvalidJumpShotSeriesException;
use App\Service\JumpShot\JumpShotNoSeriesToRevertException;
use App\Service\JumpShot\JumpShotSessionAccessDeniedException;
use App\Service\JumpShot\JumpShotSessionAlreadyFinishedException;
use App\Service\JumpShot\JumpShotSessionNotFoundException;
use App\Service\JumpShot\JumpShotSessionService;
use App\Service\JumpShot\JumpShotStatsService;
use App\Service\JumpShot\NoLinkedPlayerException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class JumpShotSessionController extends AbstractController
{
    public function __construct(
        private JumpShotSessionService $service,
        private JumpShotStatsService $statsService,
        private ImpersonationResolver $impersonation,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/jump-shot-sessions', name: 'api_jump_shot_sessions_start', methods: ['POST'])]
    public function start(Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) {
            $res = $this->service->start($token);

            return new JsonResponse($this->serializer->serialize($res, 'json'), 201, [], true);
        });
    }

    #[Route('/api/jump-shot-sessions/current', name: 'api_jump_shot_sessions_current', methods: ['GET'])]
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

    #[Route('/api/jump-shot-sessions/stats', name: 'api_jump_shot_sessions_stats', methods: ['GET'])]
    public function stats(Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) use ($request) {
            try {
                $dateRange = StatsDateRangeResolver::fromRequest($request);
            } catch (\InvalidArgumentException $e) {
                return new JsonResponse(['message' => $e->getMessage()], 400);
            }

            $impersonatePlayerId = $this->impersonation->resolveOptionalFromToken($token, $request);
            $res = $this->statsService->stats($token, $dateRange, $impersonatePlayerId);

            return new JsonResponse($this->serializer->serialize($res, 'json'), 200, [], true);
        });
    }

    #[Route('/api/jump-shot-sessions/history', name: 'api_jump_shot_sessions_history', methods: ['GET'])]
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

    #[Route('/api/jump-shot-sessions/{id}', name: 'api_jump_shot_sessions_get', methods: ['GET'])]
    public function getOne(int $id, Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) use ($id, $request) {
            try {
                $impersonatePlayerId = $this->impersonation->resolveOptionalFromToken($token, $request);
                $res = $this->service->getSummary($token, $id, $impersonatePlayerId);

                return new JsonResponse($this->serializer->serialize($res, 'json'), 200, [], true);
            } catch (JumpShotSessionNotFoundException) {
                return new JsonResponse(['message' => 'Not found'], 404);
            } catch (JumpShotSessionAccessDeniedException) {
                return new JsonResponse(['message' => 'Forbidden'], 403);
            }
        });
    }

    #[Route('/api/jump-shot-sessions/{id}/series', name: 'api_jump_shot_sessions_submit_series', methods: ['POST'])]
    public function submitSeries(int $id, Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) use ($id, $request) {
            /** @var SubmitJumpShotSeriesRequest $input */
            $input = $this->serializer->deserialize($request->getContent(), SubmitJumpShotSeriesRequest::class, 'json');
            $violations = $this->validator->validate($input);
            if (\count($violations) > 0) {
                return $this->validationError($violations);
            }

            try {
                $res = $this->service->submitSeries($token, $id, $input);

                return new JsonResponse($this->serializer->serialize($res, 'json'), 200, [], true);
            } catch (JumpShotSessionNotFoundException) {
                return new JsonResponse(['message' => 'Not found'], 404);
            } catch (JumpShotSessionAccessDeniedException) {
                return new JsonResponse(['message' => 'Forbidden'], 403);
            } catch (JumpShotSessionAlreadyFinishedException) {
                return new JsonResponse(['message' => 'Session already finished.'], 409);
            } catch (InvalidJumpShotSeriesException $e) {
                return new JsonResponse(['errors' => $e->errors], 400);
            }
        });
    }

    #[Route('/api/jump-shot-sessions/{id}/series/last', name: 'api_jump_shot_sessions_revert_last_series', methods: ['DELETE'])]
    public function revertLastSeries(int $id, Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) use ($id) {
            try {
                $res = $this->service->revertLastSeries($token, $id);

                return new JsonResponse($this->serializer->serialize($res, 'json'), 200, [], true);
            } catch (JumpShotSessionNotFoundException) {
                return new JsonResponse(['message' => 'Not found'], 404);
            } catch (JumpShotSessionAccessDeniedException) {
                return new JsonResponse(['message' => 'Forbidden'], 403);
            } catch (JumpShotNoSeriesToRevertException) {
                return new JsonResponse(['message' => 'No series to revert.'], 409);
            }
        });
    }

    #[Route('/api/jump-shot-sessions/{id}', name: 'api_jump_shot_sessions_abandon', methods: ['DELETE'])]
    public function abandon(int $id, Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) use ($id) {
            try {
                $this->service->abandon($token, $id);

                return new JsonResponse(null, 204);
            } catch (JumpShotSessionNotFoundException) {
                return new JsonResponse(['message' => 'Not found'], 404);
            } catch (JumpShotSessionAccessDeniedException) {
                return new JsonResponse(['message' => 'Forbidden'], 403);
            } catch (JumpShotSessionAlreadyFinishedException) {
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
