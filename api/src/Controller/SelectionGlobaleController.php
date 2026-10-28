<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Request\SelectionGlobaleCoachEmailRequest;
use App\Dto\Request\UpsertSelectionGlobaleRequest;
use App\Entity\SelectionGlobale;
use App\Service\PlayerAlreadyInSelectionGlobaleException;
use App\Service\PlayerNotFoundException;
use App\Service\SelectionGlobaleNotFoundException;
use App\Service\SelectionGlobaleService;
use App\Service\UserNotFoundException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class SelectionGlobaleController extends AbstractController
{
    public function __construct(
        private SelectionGlobaleService $selectionGlobaleService,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/selections-globales', name: 'api_selections_globales_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function list(): JsonResponse
    {
        $items = $this->selectionGlobaleService->listAll();
        $json = $this->serializer->serialize($items, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/selections-globales/{id}', name: 'api_selections_globales_detail', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function detail(int $id): JsonResponse
    {
        try {
            $res = $this->selectionGlobaleService->getDetail($id);
        } catch (SelectionGlobaleNotFoundException) {
            return new JsonResponse(['message' => 'Selection globale not found.'], 404);
        }

        $json = $this->serializer->serialize($res, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/selections-globales', name: 'api_selections_globales_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): JsonResponse
    {
        /** @var UpsertSelectionGlobaleRequest $input */
        $input = $this->serializer->deserialize($request->getContent(), UpsertSelectionGlobaleRequest::class, 'json');
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        $output = $this->selectionGlobaleService->create($input);
        $json = $this->serializer->serialize($output, 'json');

        return new JsonResponse($json, 201, [], true);
    }

    #[Route('/api/selections-globales/{id}', name: 'api_selections_globales_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(#[MapEntity] SelectionGlobale $selectionGlobale, Request $request): JsonResponse
    {
        /** @var UpsertSelectionGlobaleRequest $input */
        $input = $this->serializer->deserialize($request->getContent(), UpsertSelectionGlobaleRequest::class, 'json');
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        $output = $this->selectionGlobaleService->update($selectionGlobale, $input);
        $json = $this->serializer->serialize($output, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/selections-globales/{id}', name: 'api_selections_globales_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(#[MapEntity] SelectionGlobale $selectionGlobale): Response
    {
        $this->selectionGlobaleService->delete($selectionGlobale);

        return new Response(null, 204);
    }

    #[Route('/api/selections-globales/{id}/coaches', name: 'api_selections_globales_add_coach', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function addCoach(#[MapEntity] SelectionGlobale $selectionGlobale, Request $request): JsonResponse
    {
        /** @var SelectionGlobaleCoachEmailRequest $input */
        $input = $this->serializer->deserialize($request->getContent(), SelectionGlobaleCoachEmailRequest::class, 'json');
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        try {
            $res = $this->selectionGlobaleService->addCoach($selectionGlobale, $input->email);
        } catch (UserNotFoundException) {
            return new JsonResponse(['message' => 'User not found.'], 404);
        }

        $json = $this->serializer->serialize($res, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/selections-globales/{id}/coaches', name: 'api_selections_globales_remove_coach', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function removeCoach(#[MapEntity] SelectionGlobale $selectionGlobale, Request $request): JsonResponse
    {
        /** @var SelectionGlobaleCoachEmailRequest $input */
        $input = $this->serializer->deserialize($request->getContent(), SelectionGlobaleCoachEmailRequest::class, 'json');
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        try {
            $res = $this->selectionGlobaleService->removeCoach($selectionGlobale, $input->email);
        } catch (UserNotFoundException) {
            return new JsonResponse(['message' => 'User not found.'], 404);
        }

        $json = $this->serializer->serialize($res, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/selections-globales/{id}/players/{playerId}', name: 'api_selections_globales_add_player', methods: ['POST'], requirements: ['id' => '\d+', 'playerId' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function addPlayer(#[MapEntity] SelectionGlobale $selectionGlobale, int $playerId): JsonResponse
    {
        try {
            $res = $this->selectionGlobaleService->addPlayer($selectionGlobale, $playerId);
        } catch (PlayerNotFoundException) {
            return new JsonResponse(['message' => 'Player not found.'], 404);
        } catch (PlayerAlreadyInSelectionGlobaleException) {
            return new JsonResponse(['message' => 'Player already in selection.'], 409);
        }

        $json = $this->serializer->serialize($res, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/selections-globales/{id}/players/{playerId}', name: 'api_selections_globales_remove_player', methods: ['DELETE'], requirements: ['id' => '\d+', 'playerId' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function removePlayer(#[MapEntity] SelectionGlobale $selectionGlobale, int $playerId): JsonResponse
    {
        try {
            $res = $this->selectionGlobaleService->removePlayer($selectionGlobale, $playerId);
        } catch (PlayerNotFoundException) {
            return new JsonResponse(['message' => 'Player not found.'], 404);
        }

        $json = $this->serializer->serialize($res, 'json');

        return new JsonResponse($json, 200, [], true);
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
