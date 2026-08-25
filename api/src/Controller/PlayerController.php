<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Request\CreatePlayerRequest;
use App\Dto\Request\SearchPlayersQuery;
use App\Dto\Response\CreatePlayerResponse;
use App\Dto\Response\PlayerItem;
use App\Enum\DistanceBucket;
use App\Enum\GameType;
use App\Enum\MatchNature;
use App\Http\StatsDateRangeResolver;
use App\Service\PlayerService;
use App\Service\PlayerStatsService;
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
        private PlayerStatsService $playerStatsService,
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
        $unlinkedOnly = $request->query->get('unlinkedOnly');
        $q->unlinkedOnly = $unlinkedOnly !== null ? filter_var($unlinkedOnly, FILTER_VALIDATE_BOOLEAN) : null;
        $items = $this->playerService->search($q);
        $json = $this->serializer->serialize($items, 'json');
        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/players/me/stats', name: 'api_players_me_stats', methods: ['GET'])]
    public function myStats(Request $request): JsonResponse
    {
        $authHeader = (string) $request->headers->get('Authorization', '');
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        }
        $token = substr($authHeader, 7);
        try {
            $dateRange = StatsDateRangeResolver::fromRequest($request);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }

        $natureParam = $request->query->get('nature');
        $nature = null;
        if ($natureParam !== null && $natureParam !== '' && $natureParam !== 'all') {
            $nature = MatchNature::tryFrom((string) $natureParam);
            if ($nature === null) {
                return new JsonResponse(['message' => 'Invalid nature filter.'], 400);
            }
        }

        $typeParam = $request->query->get('type');
        $type = null;
        if ($typeParam !== null && $typeParam !== '' && $typeParam !== 'all') {
            $type = GameType::tryFrom((string) $typeParam);
            if ($type === null) {
                return new JsonResponse(['message' => 'Invalid type filter.'], 400);
            }
        }

        $distanceParam = $request->query->get('distance');
        $distance = null;
        if ($distanceParam !== null && $distanceParam !== '' && $distanceParam !== 'all') {
            $distance = DistanceBucket::tryFrom((string) $distanceParam);
            if ($distance === null) {
                return new JsonResponse(['message' => 'Invalid distance filter.'], 400);
            }
        }

        $competitionParam = $request->query->get('competitionId');
        $competitionId = null;
        if ($competitionParam !== null && $competitionParam !== '' && $competitionParam !== 'all') {
            if (!is_numeric($competitionParam) || (int) $competitionParam <= 0) {
                return new JsonResponse(['message' => 'Invalid competition filter.'], 400);
            }
            $competitionId = (int) $competitionParam;
        }

        $res = $this->playerStatsService->statsForToken($token, $nature, $dateRange, $type, $distance, $competitionId);
        $json = $this->serializer->serialize($res, 'json');
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
