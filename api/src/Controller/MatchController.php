<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Request\CreateMatchRequest;
use App\Service\MatchService;
use App\Service\MatchValidationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class MatchController extends AbstractController
{
    public function __construct(
        private MatchService $service,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {}

    /**
     * Expected payload:
     * {
     *   "type": "tete_a_tete"|"doublette"|"triplette",
     *   "targetScore": 13,
     *   "teamA": [1,2,(3?)],
     *   "teamB": [4,5,(6?)]
     * }
     */
    #[Route('/api/matches', name: 'api_matches_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            /** @var CreateMatchRequest $input */
            $input = $this->serializer->deserialize($request->getContent(), CreateMatchRequest::class, 'json');
            // Basic DTO validation (static constraints); dynamic rules are in service to keep same messages
            $violations = $this->validator->validate($input);
            if (\count($violations) > 0) {
                // Map to the existing error shape with first relevant field
                $errors = [];
                foreach ($violations as $v) {
                    $field = $v->getPropertyPath();
                    $errors[$field] = $v->getMessage();
                }
                return new JsonResponse(['errors' => $errors], 400);
            }

            $res = $this->service->create($input);
            $json = $this->serializer->serialize($res, 'json');
            return new JsonResponse($json, 201, [], true);
        } catch (MatchValidationException $e) {
            return new JsonResponse(['errors' => $e->errors], 400);
        }
    }
}
