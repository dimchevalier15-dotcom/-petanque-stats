<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\MatchShareNotFoundException;
use App\Service\MatchShareService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class SharedMatchController extends AbstractController
{
    public function __construct(
        private MatchShareService $matchShareService,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/shared-matches/{uuid}', name: 'api_shared_matches_get', methods: ['GET'])]
    public function get(string $uuid): JsonResponse
    {
        try {
            $output = $this->matchShareService->getPublicRecap($uuid);
        } catch (MatchShareNotFoundException) {
            return new JsonResponse(['message' => 'Not found'], 404);
        }

        $json = $this->serializer->serialize($output, 'json');

        return new JsonResponse($json, 200, [], true);
    }
}
