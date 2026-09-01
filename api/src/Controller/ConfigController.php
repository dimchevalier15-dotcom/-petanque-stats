<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Response\AppVersionConfigResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ConfigController extends AbstractController
{
    public function __construct(
        #[Autowire(param: 'app.android_latest_version')]
        private readonly string $latestVersion,
        #[Autowire(param: 'app.android_minimum_version')]
        private readonly string $minimumVersion,
        #[Autowire(param: 'app.android_store_url')]
        private readonly string $androidStoreUrl,
    ) {
    }

    #[Route('/api/config', name: 'api_config', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return $this->json(new AppVersionConfigResponse(
            latestVersion: $this->latestVersion,
            minimumVersion: $this->minimumVersion,
            androidStoreUrl: $this->androidStoreUrl,
        ));
    }
}
