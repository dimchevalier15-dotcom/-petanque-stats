<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\CountryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class CountryController extends AbstractController
{
    public function __construct(
        private CountryService $countryService,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/countries', name: 'api_countries_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $items = $this->countryService->listAll();
        $json = $this->serializer->serialize($items, 'json');

        return new JsonResponse($json, 200, [], true);
    }
}
