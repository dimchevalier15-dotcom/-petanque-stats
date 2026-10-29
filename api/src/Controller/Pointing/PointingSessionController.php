<?php

declare(strict_types=1);

namespace App\Controller\Pointing;

use App\Dto\Request\CompletePointingSessionRequest;
use App\Service\Auth\InvalidTokenException;
use App\Service\Pointing\InvalidPointingSessionStructureException;
use App\Service\Pointing\PointingSessionAccessDeniedException;
use App\Service\Pointing\PointingSessionAlreadyFinishedException;
use App\Service\Pointing\PointingSessionNotFoundException;
use App\Service\Pointing\PointingSessionService;
use App\Service\Pointing\NoLinkedPlayerException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class PointingSessionController extends AbstractController
{
    public function __construct(
        private PointingSessionService $service,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/pointing-sessions', name: 'api_pointing_sessions_start', methods: ['POST'])]
    public function start(Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) {
            $res = $this->service->start($token);

            return new JsonResponse($this->serializer->serialize($res, 'json'), 201, [], true);
        });
    }

    #[Route('/api/pointing-sessions/current', name: 'api_pointing_sessions_current', methods: ['GET'])]
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

    #[Route('/api/pointing-sessions/{id}', name: 'api_pointing_sessions_get', methods: ['GET'])]
    public function getOne(int $id, Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) use ($id) {
            try {
                $res = $this->service->getSummary($token, $id);

                return new JsonResponse($this->serializer->serialize($res, 'json'), 200, [], true);
            } catch (PointingSessionNotFoundException) {
                return new JsonResponse(['message' => 'Not found'], 404);
            } catch (PointingSessionAccessDeniedException) {
                return new JsonResponse(['message' => 'Forbidden'], 403);
            }
        });
    }

    #[Route('/api/pointing-sessions/{id}/complete', name: 'api_pointing_sessions_complete', methods: ['POST'])]
    public function complete(int $id, Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) use ($id, $request) {
            /** @var CompletePointingSessionRequest $input */
            $input = $this->serializer->deserialize($request->getContent(), CompletePointingSessionRequest::class, 'json');
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
            } catch (PointingSessionNotFoundException) {
                return new JsonResponse(['message' => 'Not found'], 404);
            } catch (PointingSessionAccessDeniedException) {
                return new JsonResponse(['message' => 'Forbidden'], 403);
            } catch (PointingSessionAlreadyFinishedException) {
                return new JsonResponse(['message' => 'Session already finished.'], 409);
            } catch (InvalidPointingSessionStructureException $e) {
                return new JsonResponse(['errors' => $e->errors], 400);
            }
        });
    }

    #[Route('/api/pointing-sessions/{id}', name: 'api_pointing_sessions_abandon', methods: ['DELETE'])]
    public function abandon(int $id, Request $request): JsonResponse
    {
        return $this->withToken($request, function (string $token) use ($id) {
            try {
                $this->service->abandon($token, $id);

                return new JsonResponse(null, 204);
            } catch (PointingSessionNotFoundException) {
                return new JsonResponse(['message' => 'Not found'], 404);
            } catch (PointingSessionAccessDeniedException) {
                return new JsonResponse(['message' => 'Forbidden'], 403);
            } catch (PointingSessionAlreadyFinishedException) {
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
