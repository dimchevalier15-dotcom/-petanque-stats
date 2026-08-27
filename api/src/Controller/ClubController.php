<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Request\UpsertClubRequest;
use App\Entity\Club;
use App\Service\ClubService;
use App\Service\CountryNotFoundException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ClubController extends AbstractController
{
    public function __construct(
        private ClubService $clubService,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/clubs', name: 'api_clubs_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $items = $this->clubService->listAll();
        $json = $this->serializer->serialize($items, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/clubs', name: 'api_clubs_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): JsonResponse
    {
        /** @var UpsertClubRequest $input */
        $input = $this->serializer->deserialize($request->getContent(), UpsertClubRequest::class, 'json');
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        try {
            $output = $this->clubService->create($input);
        } catch (CountryNotFoundException) {
            return new JsonResponse(['errors' => ['countryId' => 'Country not found.']], 400);
        }

        $json = $this->serializer->serialize($output, 'json');

        return new JsonResponse($json, 201, [], true);
    }

    #[Route('/api/clubs/{id}', name: 'api_clubs_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(#[MapEntity] Club $club, Request $request): JsonResponse
    {
        /** @var UpsertClubRequest $input */
        $input = $this->serializer->deserialize($request->getContent(), UpsertClubRequest::class, 'json');
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        try {
            $output = $this->clubService->update($club, $input);
        } catch (CountryNotFoundException) {
            return new JsonResponse(['errors' => ['countryId' => 'Country not found.']], 400);
        }

        $json = $this->serializer->serialize($output, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/clubs/{id}', name: 'api_clubs_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(#[MapEntity] Club $club): Response
    {
        $this->clubService->delete($club);

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
