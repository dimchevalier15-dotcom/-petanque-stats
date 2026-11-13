<?php

declare(strict_types=1);

namespace App\Controller\Tournament;

use App\Service\Tournament\TournamentCatalogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PublicTournamentController extends AbstractController
{
    public function __construct(
        private TournamentHttp $http,
        private TournamentCatalogService $catalog,
    ) {
    }

    #[Route(
        '/api/public/tournaments/{uuid}',
        name: 'api_public_tournaments_show',
        methods: ['GET'],
        requirements: ['uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'],
    )]
    public function show(string $uuid): Response
    {
        return $this->http->respond(fn () => $this->catalog->publicDetail($uuid));
    }
}
