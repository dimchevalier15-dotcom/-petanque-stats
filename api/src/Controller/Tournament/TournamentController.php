<?php

declare(strict_types=1);

namespace App\Controller\Tournament;

use App\Dto\Request\AddTournamentMemberRequest;
use App\Dto\Request\AddTournamentTeamPlayerRequest;
use App\Dto\Request\CreateTournamentRequest;
use App\Dto\Request\CreateTournamentTeamRequest;
use App\Dto\Request\RenameTournamentTeamRequest;
use App\Dto\Request\UpdateTournamentMemberRequest;
use App\Dto\Request\UpdateTournamentRequest;
use App\Service\Tournament\TournamentCatalogService;
use App\Service\Tournament\TournamentRosterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TournamentController extends AbstractController
{
    public function __construct(
        private TournamentHttp $http,
        private TournamentCatalogService $catalog,
        private TournamentRosterService $roster,
    ) {
    }

    #[Route('/api/tournaments', name: 'api_tournaments_list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->http->respond(fn () => $this->catalog->listFor($this->http->user($this->getUser())));
    }

    #[Route('/api/tournaments', name: 'api_tournaments_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        return $this->http->respond(function () use ($request) {
            /** @var CreateTournamentRequest $input */
            $input = $this->http->body($request, CreateTournamentRequest::class);

            return $this->catalog->create($this->http->user($this->getUser()), $input);
        }, 201);
    }

    #[Route('/api/tournaments/user-search', name: 'api_tournaments_user_search', methods: ['GET'])]
    public function searchUsers(Request $request): Response
    {
        return $this->http->respond(fn () => $this->catalog->searchUsers(
            $this->http->user($this->getUser()),
            (string) $request->query->get('q', ''),
        ));
    }

    #[Route('/api/tournaments/{tournamentId}', name: 'api_tournaments_show', methods: ['GET'], requirements: ['tournamentId' => '\d+'])]
    public function show(int $tournamentId): Response
    {
        return $this->http->respond(fn () => $this->catalog->detail($this->http->user($this->getUser()), $tournamentId));
    }

    #[Route('/api/tournaments/{tournamentId}', name: 'api_tournaments_update', methods: ['PUT'], requirements: ['tournamentId' => '\d+'])]
    public function update(int $tournamentId, Request $request): Response
    {
        return $this->http->respond(function () use ($tournamentId, $request) {
            /** @var UpdateTournamentRequest $input */
            $input = $this->http->body($request, UpdateTournamentRequest::class);

            return $this->catalog->update($this->http->user($this->getUser()), $tournamentId, $input);
        });
    }

    #[Route('/api/tournaments/{tournamentId}/members', name: 'api_tournaments_add_member', methods: ['POST'], requirements: ['tournamentId' => '\d+'])]
    public function addMember(int $tournamentId, Request $request): Response
    {
        return $this->http->respond(function () use ($tournamentId, $request) {
            /** @var AddTournamentMemberRequest $input */
            $input = $this->http->body($request, AddTournamentMemberRequest::class);

            return $this->roster->addMember($this->http->user($this->getUser()), $tournamentId, $input);
        }, 201);
    }

    #[Route('/api/tournaments/{tournamentId}/members/{userId}', name: 'api_tournaments_update_member', methods: ['PATCH'], requirements: ['tournamentId' => '\d+', 'userId' => '\d+'])]
    public function updateMember(int $tournamentId, int $userId, Request $request): Response
    {
        return $this->http->respond(function () use ($tournamentId, $userId, $request) {
            /** @var UpdateTournamentMemberRequest $input */
            $input = $this->http->body($request, UpdateTournamentMemberRequest::class);

            return $this->roster->updateMember($this->http->user($this->getUser()), $tournamentId, $userId, $input->admin);
        });
    }

    #[Route('/api/tournaments/{tournamentId}/members/{userId}', name: 'api_tournaments_remove_member', methods: ['DELETE'], requirements: ['tournamentId' => '\d+', 'userId' => '\d+'])]
    public function removeMember(int $tournamentId, int $userId): Response
    {
        return $this->http->respond(
            fn () => $this->roster->removeMember($this->http->user($this->getUser()), $tournamentId, $userId),
        );
    }

    #[Route('/api/tournaments/{tournamentId}/teams', name: 'api_tournaments_create_team', methods: ['POST'], requirements: ['tournamentId' => '\d+'])]
    public function createTeam(int $tournamentId, Request $request): Response
    {
        return $this->http->respond(function () use ($tournamentId, $request) {
            /** @var CreateTournamentTeamRequest $input */
            $input = $this->http->body($request, CreateTournamentTeamRequest::class);

            return $this->roster->createTeam($this->http->user($this->getUser()), $tournamentId, $input);
        }, 201);
    }

    #[Route('/api/tournaments/{tournamentId}/teams/{teamId}', name: 'api_tournaments_rename_team', methods: ['PATCH'], requirements: ['tournamentId' => '\d+', 'teamId' => '\d+'])]
    public function renameTeam(int $tournamentId, int $teamId, Request $request): Response
    {
        return $this->http->respond(function () use ($tournamentId, $teamId, $request) {
            /** @var RenameTournamentTeamRequest $input */
            $input = $this->http->body($request, RenameTournamentTeamRequest::class);

            return $this->roster->renameTeam($this->http->user($this->getUser()), $tournamentId, $teamId, $input);
        });
    }

    #[Route('/api/tournaments/{tournamentId}/teams/{teamId}', name: 'api_tournaments_delete_team', methods: ['DELETE'], requirements: ['tournamentId' => '\d+', 'teamId' => '\d+'])]
    public function deleteTeam(int $tournamentId, int $teamId): Response
    {
        return $this->http->respond(
            fn () => $this->roster->deleteTeam($this->http->user($this->getUser()), $tournamentId, $teamId),
        );
    }

    #[Route('/api/tournaments/{tournamentId}/teams/{teamId}/players', name: 'api_tournaments_add_player', methods: ['POST'], requirements: ['tournamentId' => '\d+', 'teamId' => '\d+'])]
    public function addPlayer(int $tournamentId, int $teamId, Request $request): Response
    {
        return $this->http->respond(function () use ($tournamentId, $teamId, $request) {
            /** @var AddTournamentTeamPlayerRequest $input */
            $input = $this->http->body($request, AddTournamentTeamPlayerRequest::class);

            return $this->roster->addPlayer($this->http->user($this->getUser()), $tournamentId, $teamId, $input);
        }, 201);
    }

    #[Route('/api/tournaments/{tournamentId}/teams/{teamId}/players/{playerId}', name: 'api_tournaments_remove_player', methods: ['DELETE'], requirements: ['tournamentId' => '\d+', 'teamId' => '\d+', 'playerId' => '\d+'])]
    public function removePlayer(int $tournamentId, int $teamId, int $playerId): Response
    {
        return $this->http->respond(
            fn () => $this->roster->removePlayer($this->http->user($this->getUser()), $tournamentId, $teamId, $playerId),
        );
    }
}
