<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Request\CreatePlayerRequest;
use App\Dto\Request\SearchPlayersQuery;
use App\Dto\Response\CreatePlayerResponse;
use App\Dto\Response\PlayerItem;
use App\Service\PlayerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class PlayerController extends AbstractController
{
    public function __construct(
        private PlayerService $playerService,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/players', name: 'api_players_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var CreatePlayerRequest $input */
        $input = $this->serializer->deserialize($request->getContent(), CreatePlayerRequest::class, 'json');
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $field = $v->getPropertyPath();
                $errors[$field] = $v->getMessage();
            }
            return new JsonResponse(['errors' => $errors], 400);
        }

        $output = $this->playerService->create($input);
        $json = $this->serializer->serialize($output, 'json');
        return new JsonResponse($json, 201, [], true);
    }

    #[Route('/api/players', name: 'api_players_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $q = new SearchPlayersQuery();
        $q->q = $request->query->get('q') !== null ? (string) $request->query->get('q') : null;
        $items = $this->playerService->search($q);
        $json = $this->serializer->serialize($items, 'json');
        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/players/{id}', name: 'api_players_get', methods: ['GET'])]
    public function getOne(int $id): JsonResponse
    {
        $item = $this->playerService->getOne($id);
        if ($item === null) {
            return new JsonResponse(['message' => 'Not found'], 404);
        }
        $json = $this->serializer->serialize($item, 'json');
        return new JsonResponse($json, 200, [], true);
    }
}
