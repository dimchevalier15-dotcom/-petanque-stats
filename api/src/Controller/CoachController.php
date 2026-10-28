<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Request\CreateCoachPlayerRequest;
use App\Dto\Request\CreatePlayerRequest;
use App\Enum\DistanceBucket;
use App\Enum\GameType;
use App\Enum\MatchNature;
use App\Enum\ShootingContextNature;
use App\Http\CoachDateRangeResolver;
use App\Service\Auth\CurrentUserService;
use App\Service\Auth\InvalidTokenException;
use App\Service\CoachAccessService;
use App\Service\CoachService;
use App\Service\MatchHistoryService;
use App\Service\PlayerAlreadyHasClubException;
use App\Service\PlayerAlreadyInSelectionGlobaleException;
use App\Service\PlayerNotFoundException;
use App\Service\SelectionGlobaleNotFoundException;
use App\Service\SelectionGlobaleService;
use App\Service\PlayerService;
use App\Service\PlayerStatsService;
use App\Service\PlayerTacticalInsightsService;
use App\Service\Shooting\ShootingStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CoachController extends AbstractController
{
    public function __construct(
        private CurrentUserService $currentUser,
        private CoachAccessService $coachAccess,
        private CoachService $coachService,
        private SelectionGlobaleService $selectionGlobaleService,
        private PlayerStatsService $playerStatsService,
        private PlayerTacticalInsightsService $playerTacticalInsightsService,
        private MatchHistoryService $matchHistoryService,
        private ShootingStatsService $shootingStatsService,
        private PlayerService $playerService,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/coach/selections-globales', name: 'api_coach_selections_globales', methods: ['GET'])]
    public function listSelectionGlobales(Request $request): JsonResponse
    {
        $user = $this->requireAuthenticatedUser($request);
        $items = $this->selectionGlobaleService->listForCoach($user);
        $json = $this->serializer->serialize($items, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/coach/players', name: 'api_coach_players', methods: ['GET'])]
    public function listPlayers(Request $request): JsonResponse
    {
        [$user, $selectionGlobaleId] = $this->requireCoachContext($request);
        try {
            $dateRange = CoachDateRangeResolver::fromRequest($request);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }

        $nature = $this->parseNature($request);
        if ($nature === false) {
            return new JsonResponse(['message' => 'Invalid nature filter.'], 400);
        }

        if ($selectionGlobaleId !== null) {
            try {
                $res = $this->coachService->listPlayersForSelectionGlobaleCoach($user, $selectionGlobaleId, $dateRange, $nature);
            } catch (SelectionGlobaleNotFoundException) {
                return new JsonResponse(['message' => 'Selection globale not found.'], 404);
            }
        } else {
            $res = $this->coachService->listPlayersForCoach($user, $dateRange, $nature);
        }
        $json = $this->serializer->serialize($res, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/coach/players', name: 'api_coach_players_create', methods: ['POST'])]
    public function createPlayer(Request $request): JsonResponse
    {
        [$user, $selectionGlobaleId] = $this->requireCoachContext($request);

        /** @var CreateCoachPlayerRequest $input */
        $input = $this->serializer->deserialize($request->getContent(), CreateCoachPlayerRequest::class, 'json');
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return new JsonResponse(['errors' => $errors], 400);
        }

        if ($selectionGlobaleId !== null) {
            try {
                $item = $this->coachService->createPlayerForSelectionGlobale(
                    $user,
                    $selectionGlobaleId,
                    trim($input->firstName),
                    trim($input->lastName),
                    $input->nickname !== null && trim($input->nickname) !== '' ? trim($input->nickname) : null,
                );
            } catch (SelectionGlobaleNotFoundException) {
                return new JsonResponse(['message' => 'Selection globale not found.'], 404);
            }
            $json = $this->serializer->serialize($item, 'json');

            return new JsonResponse($json, 201, [], true);
        }

        $clubId = $this->coachAccess->requireCoachClubId($user);

        $createRequest = new CreatePlayerRequest();
        $createRequest->firstName = trim($input->firstName);
        $createRequest->lastName = trim($input->lastName);
        $createRequest->nickname = $input->nickname !== null && trim($input->nickname) !== ''
            ? trim($input->nickname)
            : null;
        $createRequest->clubId = $clubId;

        try {
            $res = $this->playerService->create($createRequest);
        } catch (\App\Service\ClubNotFoundException) {
            return new JsonResponse(['errors' => ['clubId' => 'Club not found.']], 400);
        }

        $json = $this->serializer->serialize($res, 'json');

        return new JsonResponse($json, 201, [], true);
    }

    #[Route('/api/coach/players/available', name: 'api_coach_players_available', methods: ['GET'])]
    public function searchAvailablePlayers(Request $request): JsonResponse
    {
        [, $selectionGlobaleId] = $this->requireCoachContext($request);
        $q = trim((string) $request->query->get('q', ''));
        if ($selectionGlobaleId !== null) {
            $items = $this->coachService->searchPlayersForSelectionAttach($q, $selectionGlobaleId);
        } else {
            $items = $this->coachService->searchPlayersWithoutClub($q);
        }
        $json = $this->serializer->serialize($items, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/coach/players/{id}/attach', name: 'api_coach_player_attach', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function attachPlayer(int $id, Request $request): JsonResponse
    {
        [$user, $selectionGlobaleId] = $this->requireCoachContext($request);

        try {
            if ($selectionGlobaleId !== null) {
                $item = $this->coachService->attachPlayerToSelectionGlobale($user, $selectionGlobaleId, $id);
            } else {
                $item = $this->coachService->attachPlayerToCoachClub($user, $id);
            }
        } catch (PlayerNotFoundException) {
            return new JsonResponse(['message' => 'Player not found.'], 404);
        } catch (PlayerAlreadyHasClubException) {
            return new JsonResponse(['message' => 'Player already belongs to a club.'], 409);
        } catch (PlayerAlreadyInSelectionGlobaleException) {
            return new JsonResponse(['message' => 'Player already in selection.'], 409);
        } catch (SelectionGlobaleNotFoundException) {
            return new JsonResponse(['message' => 'Selection globale not found.'], 404);
        }

        $json = $this->serializer->serialize($item, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/coach/players/{id}/shooting/stats', name: 'api_coach_player_shooting_stats', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function playerShootingStats(int $id, Request $request): JsonResponse
    {
        [$user, $selectionGlobaleId] = $this->requireCoachContext($request);
        $this->coachAccess->assertCoachCanViewPlayer($user, $id, $selectionGlobaleId);

        try {
            $dateRange = CoachDateRangeResolver::fromRequest($request);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }

        $shootingNature = $this->parseShootingNature($request);
        if ($shootingNature === false) {
            return new JsonResponse(['message' => 'Invalid nature filter.'], 400);
        }

        $res = $this->shootingStatsService->statsForPlayerId($id, $shootingNature, $dateRange);
        $json = $this->serializer->serialize($res, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/coach/players/{id}/stats', name: 'api_coach_player_stats', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function playerStats(int $id, Request $request): JsonResponse
    {
        [$user, $selectionGlobaleId] = $this->requireCoachContext($request);
        $player = $this->coachAccess->assertCoachCanViewPlayer($user, $id, $selectionGlobaleId);

        try {
            $dateRange = CoachDateRangeResolver::fromRequest($request);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }

        $nature = $this->parseNature($request);
        if ($nature === false) {
            return new JsonResponse(['message' => 'Invalid nature filter.'], 400);
        }

        $type = $this->parseType($request);
        if ($type === false) {
            return new JsonResponse(['message' => 'Invalid type filter.'], 400);
        }

        $distance = $this->parseDistance($request);
        if ($distance === false) {
            return new JsonResponse(['message' => 'Invalid distance filter.'], 400);
        }

        $competitionId = $this->parseCompetitionId($request);
        if ($competitionId === false) {
            return new JsonResponse(['message' => 'Invalid competition filter.'], 400);
        }

        $displayName = trim($player->getFirstName().' '.$player->getLastName());
        if ($player->getNickname() !== '') {
            $displayName = $player->getNickname().' ('.$displayName.')';
        }

        $res = $this->playerStatsService->statsForPlayerId(
            $id,
            $displayName,
            $nature,
            $dateRange,
            $type,
            $distance,
            $competitionId,
        );
        $json = $this->serializer->serialize($res, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/coach/players/{id}/stats/tactical-insights', name: 'api_coach_player_tactical_insights', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function playerTacticalInsights(int $id, Request $request): JsonResponse
    {
        [$user, $selectionGlobaleId] = $this->requireCoachContext($request);
        $this->coachAccess->assertCoachCanViewPlayer($user, $id, $selectionGlobaleId);

        try {
            $dateRange = CoachDateRangeResolver::fromRequest($request);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }

        $nature = $this->parseNature($request);
        if ($nature === false) {
            return new JsonResponse(['message' => 'Invalid nature filter.'], 400);
        }

        $type = $this->parseType($request);
        if ($type === false) {
            return new JsonResponse(['message' => 'Invalid type filter.'], 400);
        }

        $distance = $this->parseDistance($request);
        if ($distance === false) {
            return new JsonResponse(['message' => 'Invalid distance filter.'], 400);
        }

        $competitionId = $this->parseCompetitionId($request);
        if ($competitionId === false) {
            return new JsonResponse(['message' => 'Invalid competition filter.'], 400);
        }

        $res = $this->playerTacticalInsightsService->insightsForPlayerId(
            $id,
            $nature,
            $dateRange,
            $type,
            $distance,
            $competitionId,
        );
        $json = $this->serializer->serialize($res, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/coach/players/{id}/matches/history', name: 'api_coach_player_history', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function playerHistory(int $id, Request $request): JsonResponse
    {
        [$user, $selectionGlobaleId] = $this->requireCoachContext($request);
        $this->coachAccess->assertCoachCanViewPlayer($user, $id, $selectionGlobaleId);

        $page = max(1, (int) $request->query->get('page', 1));
        $pageSize = min(50, max(1, (int) $request->query->get('pageSize', 20)));

        $res = $this->matchHistoryService->historyForPlayerId($id, $page, $pageSize);
        $json = $this->serializer->serialize($res, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    private function requireAuthenticatedUser(Request $request): \App\Entity\User
    {
        $authHeader = (string) $request->headers->get('Authorization', '');
        if (!str_starts_with($authHeader, 'Bearer ')) {
            throw $this->createAccessDeniedException();
        }

        $token = substr($authHeader, 7);
        try {
            return $this->currentUser->getUserFromToken($token);
        } catch (InvalidTokenException) {
            throw $this->createAccessDeniedException();
        }
    }

    /**
     * @return array{0:\App\Entity\User,1:int|null}
     */
    private function requireCoachContext(Request $request): array
    {
        $user = $this->requireAuthenticatedUser($request);
        $selectionGlobaleId = $this->parseSelectionGlobaleId($request);
        if ($selectionGlobaleId === false) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Invalid selectionGlobaleId filter.');
        }

        if ($selectionGlobaleId !== null) {
            $this->coachAccess->assertUserCoachesSelection($user, $selectionGlobaleId);

            return [$user, $selectionGlobaleId];
        }

        if (!$user->isCoach()) {
            throw $this->createAccessDeniedException();
        }

        return [$user, null];
    }

    private function parseSelectionGlobaleId(Request $request): int|null|false
    {
        $param = $request->query->get('selectionGlobaleId');
        if ($param === null || $param === '') {
            return null;
        }

        if (!is_numeric($param) || (int) $param <= 0) {
            return false;
        }

        return (int) $param;
    }

    private function parseNature(Request $request): MatchNature|null|false
    {
        $natureParam = $request->query->get('nature');
        if ($natureParam === null || $natureParam === '' || $natureParam === 'all') {
            return null;
        }

        return MatchNature::tryFrom((string) $natureParam) ?? false;
    }

    private function parseShootingNature(Request $request): ShootingContextNature|null|false
    {
        $natureParam = $request->query->get('nature');
        if ($natureParam === null || $natureParam === '' || $natureParam === 'all') {
            return null;
        }

        return ShootingContextNature::tryFrom((string) $natureParam) ?? false;
    }

    private function parseType(Request $request): GameType|null|false
    {
        $typeParam = $request->query->get('type');
        if ($typeParam === null || $typeParam === '' || $typeParam === 'all') {
            return null;
        }

        return GameType::tryFrom((string) $typeParam) ?? false;
    }

    private function parseDistance(Request $request): DistanceBucket|null|false
    {
        $distanceParam = $request->query->get('distance');
        if ($distanceParam === null || $distanceParam === '' || $distanceParam === 'all') {
            return null;
        }

        return DistanceBucket::tryFrom((string) $distanceParam) ?? false;
    }

    private function parseCompetitionId(Request $request): int|null|false
    {
        $competitionParam = $request->query->get('competitionId');
        if ($competitionParam === null || $competitionParam === '' || $competitionParam === 'all') {
            return null;
        }

        if (!is_numeric($competitionParam) || (int) $competitionParam <= 0) {
            return false;
        }

        return (int) $competitionParam;
    }
}
