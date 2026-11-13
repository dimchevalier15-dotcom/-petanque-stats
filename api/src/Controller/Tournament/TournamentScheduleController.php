<?php

declare(strict_types=1);

namespace App\Controller\Tournament;

use App\Dto\Request\CreateCatchUpMatchRequest;
use App\Dto\Request\GenerateTournamentDayRequest;
use App\Dto\Request\UpdateTournamentMatchLaneRequest;
use App\Dto\Request\UpdateTournamentMatchScoreRequest;
use App\Dto\Request\UpdateTournamentMatchTeamsRequest;
use App\Service\Tournament\TournamentScheduleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TournamentScheduleController extends AbstractController
{
    public function __construct(
        private TournamentHttp $http,
        private TournamentScheduleService $schedule,
    ) {
    }

    #[Route('/api/tournaments/{tournamentId}/ranking', name: 'api_tournaments_ranking', methods: ['GET'], requirements: ['tournamentId' => '\d+'])]
    public function ranking(int $tournamentId): Response
    {
        return $this->http->respond(fn () => $this->schedule->ranking($this->http->user($this->getUser()), $tournamentId));
    }

    #[Route('/api/tournaments/{tournamentId}/days', name: 'api_tournaments_generate_day', methods: ['POST'], requirements: ['tournamentId' => '\d+'])]
    public function generateDay(int $tournamentId, Request $request): Response
    {
        return $this->http->respond(function () use ($tournamentId, $request) {
            /** @var GenerateTournamentDayRequest $input */
            $input = $this->http->body($request, GenerateTournamentDayRequest::class);

            return $this->schedule->generateDay($this->http->user($this->getUser()), $tournamentId, $input);
        }, 201);
    }

    #[Route('/api/tournaments/{tournamentId}/days/{dayId}/close', name: 'api_tournaments_close_day', methods: ['POST'], requirements: ['tournamentId' => '\d+', 'dayId' => '\d+'])]
    public function closeDay(int $tournamentId, int $dayId): Response
    {
        return $this->http->respond(
            fn () => $this->schedule->closeDay($this->http->user($this->getUser()), $tournamentId, $dayId),
        );
    }

    #[Route('/api/tournaments/{tournamentId}/days/{dayId}', name: 'api_tournaments_delete_day', methods: ['DELETE'], requirements: ['tournamentId' => '\d+', 'dayId' => '\d+'])]
    public function deleteDay(int $tournamentId, int $dayId): Response
    {
        return $this->http->respond(
            fn () => $this->schedule->deleteDay($this->http->user($this->getUser()), $tournamentId, $dayId),
        );
    }

    #[Route('/api/tournaments/{tournamentId}/matches/{matchId}/lane', name: 'api_tournaments_update_lane', methods: ['PATCH'], requirements: ['tournamentId' => '\d+', 'matchId' => '\d+'])]
    public function updateLane(int $tournamentId, int $matchId, Request $request): Response
    {
        return $this->http->respond(function () use ($tournamentId, $matchId, $request) {
            /** @var UpdateTournamentMatchLaneRequest $input */
            $input = $this->http->body($request, UpdateTournamentMatchLaneRequest::class);

            return $this->schedule->updateLane($this->http->user($this->getUser()), $tournamentId, $matchId, $input);
        });
    }

    #[Route('/api/tournaments/{tournamentId}/matches/{matchId}/score', name: 'api_tournaments_update_score', methods: ['PATCH'], requirements: ['tournamentId' => '\d+', 'matchId' => '\d+'])]
    public function updateScore(int $tournamentId, int $matchId, Request $request): Response
    {
        return $this->http->respond(function () use ($tournamentId, $matchId, $request) {
            /** @var UpdateTournamentMatchScoreRequest $input */
            $input = $this->http->body($request, UpdateTournamentMatchScoreRequest::class);

            return $this->schedule->updateScore($this->http->user($this->getUser()), $tournamentId, $matchId, $input);
        });
    }

    #[Route('/api/tournaments/{tournamentId}/matches/{matchId}', name: 'api_tournaments_delete_match', methods: ['DELETE'], requirements: ['tournamentId' => '\d+', 'matchId' => '\d+'])]
    public function deleteMatch(int $tournamentId, int $matchId): Response
    {
        return $this->http->respond(
            fn () => $this->schedule->deleteMatch($this->http->user($this->getUser()), $tournamentId, $matchId),
        );
    }

    #[Route('/api/tournaments/{tournamentId}/matches/{matchId}/teams', name: 'api_tournaments_update_teams', methods: ['PATCH'], requirements: ['tournamentId' => '\d+', 'matchId' => '\d+'])]
    public function updateTeams(int $tournamentId, int $matchId, Request $request): Response
    {
        return $this->http->respond(function () use ($tournamentId, $matchId, $request) {
            /** @var UpdateTournamentMatchTeamsRequest $input */
            $input = $this->http->body($request, UpdateTournamentMatchTeamsRequest::class);

            return $this->schedule->updateTeams($this->http->user($this->getUser()), $tournamentId, $matchId, $input);
        });
    }

    #[Route('/api/tournaments/{tournamentId}/playoffs', name: 'api_tournaments_generate_playoffs', methods: ['POST'], requirements: ['tournamentId' => '\d+'])]
    public function generatePlayoffs(int $tournamentId): Response
    {
        return $this->http->respond(
            fn () => $this->schedule->generatePlayoffs($this->http->user($this->getUser()), $tournamentId),
            201,
        );
    }

    #[Route('/api/tournaments/{tournamentId}/catch-up-matches', name: 'api_tournaments_create_catch_up_match', methods: ['POST'], requirements: ['tournamentId' => '\d+'])]
    public function createCatchUpMatch(int $tournamentId, Request $request): Response
    {
        return $this->http->respond(function () use ($tournamentId, $request) {
            /** @var CreateCatchUpMatchRequest $input */
            $input = $this->http->body($request, CreateCatchUpMatchRequest::class);

            return $this->schedule->createCatchUpMatch($this->http->user($this->getUser()), $tournamentId, $input);
        }, 201);
    }
}
