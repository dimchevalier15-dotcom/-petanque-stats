<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Request\UpsertLiveMatchRequest;
use App\Service\LiveMatchFinishedException;
use App\Service\LiveMatchNotActiveException;
use App\Service\LiveMatchNotFoundException;
use App\Service\LiveMatchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class LiveMatchController extends AbstractController
{
    public function __construct(
        private LiveMatchService $liveMatchService,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/live-matches', name: 'api_live_matches_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        /** @var UpsertLiveMatchRequest $input */
        $input = $this->serializer->deserialize($request->getContent(), UpsertLiveMatchRequest::class, 'json');
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        $output = $this->liveMatchService->create($input);
        $json = $this->serializer->serialize($output, 'json');

        return new JsonResponse($json, 201, [], true);
    }

    #[Route('/api/live-matches/{uuid}', name: 'api_live_matches_get', methods: ['GET'])]
    public function get(string $uuid): JsonResponse
    {
        try {
            $output = $this->liveMatchService->getByUuid($uuid);
        } catch (LiveMatchNotFoundException) {
            return new JsonResponse(['error' => 'Live match not found.'], 404);
        }

        $json = $this->serializer->serialize($output, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/live-matches/{uuid}', name: 'api_live_matches_update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function update(string $uuid, Request $request): JsonResponse
    {
        /** @var UpsertLiveMatchRequest $input */
        $input = $this->serializer->deserialize($request->getContent(), UpsertLiveMatchRequest::class, 'json');
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        try {
            $output = $this->liveMatchService->update($uuid, $input);
        } catch (LiveMatchNotFoundException) {
            return new JsonResponse(['error' => 'Live match not found.'], 404);
        } catch (LiveMatchFinishedException) {
            return new JsonResponse(['error' => 'Live match is finished.'], 409);
        }

        $json = $this->serializer->serialize($output, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/live-matches/{uuid}/finish', name: 'api_live_matches_finish', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function finish(string $uuid): JsonResponse
    {
        try {
            $output = $this->liveMatchService->finish($uuid);
        } catch (LiveMatchNotFoundException) {
            return new JsonResponse(['error' => 'Live match not found.'], 404);
        } catch (LiveMatchNotActiveException) {
            return new JsonResponse(['error' => 'Live match is not active.'], 409);
        }

        $json = $this->serializer->serialize($output, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/live-matches/{uuid}', name: 'api_live_matches_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(string $uuid): Response
    {
        try {
            $this->liveMatchService->delete($uuid);
        } catch (LiveMatchNotFoundException) {
            return new JsonResponse(['error' => 'Live match not found.'], 404);
        }

        return new Response(null, 204);
    }

    private function validationErrorResponse(iterable $violations): JsonResponse
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return new JsonResponse(['errors' => $errors], 400);
    }
}
