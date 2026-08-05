<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Player;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class PlayerController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    #[Route('/api/players', name: 'api_players_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var array{firstName?: mixed, lastName?: mixed, nickname?: mixed} $payload */
        $payload = (array) json_decode($request->getContent(), true);

        $firstName = isset($payload['firstName']) && \is_string($payload['firstName']) ? trim($payload['firstName']) : '';
        $lastName = isset($payload['lastName']) && \is_string($payload['lastName']) ? trim($payload['lastName']) : '';
        $nickname = isset($payload['nickname']) && \is_string($payload['nickname']) ? trim($payload['nickname']) : '';

        $errors = [];
        if ($firstName === '') {
            $errors['firstName'] = 'This field is required.';
        }
        if ($lastName === '') {
            $errors['lastName'] = 'This field is required.';
        }

        if ($errors !== []) {
            return $this->json(['errors' => $errors], 400);
        }

        $player = new Player(
            firstName: $firstName,
            lastName: $lastName,
            nickname: $nickname !== '' ? $nickname : $firstName,
        );
        // Ensure new player is not linked to any user
        $player->setUser(null);

        $this->em->persist($player);
        $this->em->flush();

        return $this->json([
            'id' => (int) $player->getId(),
            'firstName' => $player->getFirstName(),
            'lastName' => $player->getLastName(),
            'nickname' => $player->getNickname(),
        ], 201);
    }
}
