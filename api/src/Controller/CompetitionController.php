<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Request\UpsertCompetitionRequest;
use App\Entity\Competition;
use App\Service\CompetitionService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CompetitionController extends AbstractController
{
    public function __construct(
        private CompetitionService $competitionService,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/competitions', name: 'api_competitions_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $items = $this->competitionService->listAll();
        $json = $this->serializer->serialize($items, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/competitions', name: 'api_competitions_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): JsonResponse
    {
        /** @var UpsertCompetitionRequest $input */
        $input = $this->serializer->deserialize($request->getContent(), UpsertCompetitionRequest::class, 'json');
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        $output = $this->competitionService->create($input);
        $json = $this->serializer->serialize($output, 'json');

        return new JsonResponse($json, 201, [], true);
    }

    #[Route('/api/competitions/{id}', name: 'api_competitions_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(#[MapEntity] Competition $competition, Request $request): JsonResponse
    {
        /** @var UpsertCompetitionRequest $input */
        $input = $this->serializer->deserialize($request->getContent(), UpsertCompetitionRequest::class, 'json');
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        $output = $this->competitionService->update($competition, $input);
        $json = $this->serializer->serialize($output, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/competitions/{id}', name: 'api_competitions_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(#[MapEntity] Competition $competition): Response
    {
        $this->competitionService->delete($competition);

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
