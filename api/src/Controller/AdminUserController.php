<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Request\UpdateUserCoachClubRequest;
use App\Service\AdminUserCoachService;
use App\Service\ClubNotFoundException;
use App\Service\UserNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AdminUserController extends AbstractController
{
    public function __construct(
        private AdminUserCoachService $adminUserCoachService,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/admin/users/coach-club', name: 'api_admin_users_coach_club', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateCoachClub(Request $request): JsonResponse
    {
        /** @var UpdateUserCoachClubRequest $input */
        $input = $this->serializer->deserialize($request->getContent(), UpdateUserCoachClubRequest::class, 'json');
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[$v->getPropertyPath()] = $v->getMessage();
            }

            return new JsonResponse(['errors' => $errors], 400);
        }

        try {
            $res = $this->adminUserCoachService->updateCoachClub($input->email, $input->clubId);
        } catch (UserNotFoundException) {
            return new JsonResponse(['message' => 'User not found.'], 404);
        } catch (ClubNotFoundException) {
            return new JsonResponse(['errors' => ['clubId' => 'Club not found.']], 400);
        }

        $json = $this->serializer->serialize($res, 'json');

        return new JsonResponse($json, 200, [], true);
    }
}
