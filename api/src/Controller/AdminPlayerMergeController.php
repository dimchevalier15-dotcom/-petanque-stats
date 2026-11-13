<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Request\MergePlayersRequest;
use App\Service\PlayerMergeException;
use App\Service\PlayerMergeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AdminPlayerMergeController extends AbstractController
{
    public function __construct(
        private PlayerMergeService $playerMergeService,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/admin/players/merge-preview', name: 'api_admin_players_merge_preview', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function preview(Request $request): JsonResponse
    {
        $keepPlayerId = $request->query->getInt('keepPlayerId');
        $dropPlayerId = $request->query->getInt('dropPlayerId');
        if ($keepPlayerId <= 0 || $dropPlayerId <= 0) {
            return new JsonResponse(['code' => 'invalid'], 400);
        }

        try {
            $preview = $this->playerMergeService->preview($keepPlayerId, $dropPlayerId);
        } catch (PlayerMergeException $e) {
            return $this->error($e);
        }

        return new JsonResponse($this->serializer->serialize($preview, 'json'), 200, [], true);
    }

    #[Route('/api/admin/players/merge', name: 'api_admin_players_merge', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function merge(Request $request): JsonResponse
    {
        /** @var MergePlayersRequest $input */
        $input = $this->serializer->deserialize($request->getContent(), MergePlayersRequest::class, 'json');
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            return new JsonResponse(['code' => 'invalid'], 400);
        }

        try {
            $result = $this->playerMergeService->merge($input->keepPlayerId, $input->dropPlayerId, $input->keepUserId);
        } catch (PlayerMergeException $e) {
            return $this->error($e);
        }

        return new JsonResponse($this->serializer->serialize($result, 'json'), 200, [], true);
    }

    private function error(PlayerMergeException $e): JsonResponse
    {
        $status = match ($e->errorCode) {
            PlayerMergeException::NOT_FOUND => 404,
            PlayerMergeException::USER_CHOICE_REQUIRED,
            PlayerMergeException::SAME_MATCH,
            PlayerMergeException::SAME_TOURNAMENT => 409,
            default => 400,
        };

        return new JsonResponse([
            'code' => $e->errorCode,
            'detail' => $e->detail,
        ], $status);
    }
}
