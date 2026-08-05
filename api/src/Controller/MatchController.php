<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Game;
use App\Entity\GameParticipant;
use App\Entity\Player;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class MatchController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

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
        /** @var array{type?: mixed, targetScore?: mixed, teamA?: mixed, teamB?: mixed} $payload */
        $payload = (array) json_decode($request->getContent(), true);

        $type = isset($payload['type']) && is_string($payload['type']) ? $payload['type'] : 'doublette';
        $allowed = ['tete_a_tete' => 1, 'doublette' => 2, 'triplette' => 3];
        if (!isset($allowed[$type])) {
            return $this->json(['errors' => ['type' => 'Invalid type.']], 400);
        }
        $expected = $allowed[$type];

        $targetScore = 13;
        if (isset($payload['targetScore'])) {
            $targetScore = (int) $payload['targetScore'];
            if ($targetScore <= 0) {
                return $this->json(['errors' => ['targetScore' => 'Must be a positive integer.']], 400);
            }
        }

        $teamA = isset($payload['teamA']) && is_array($payload['teamA']) ? array_values($payload['teamA']) : [];
        $teamB = isset($payload['teamB']) && is_array($payload['teamB']) ? array_values($payload['teamB']) : [];

        if (count($teamA) !== $expected) {
            return $this->json(['errors' => ['teamA' => 'Invalid number of players for team A.']], 400);
        }
        if (count($teamB) !== $expected) {
            return $this->json(['errors' => ['teamB' => 'Invalid number of players for team B.']], 400);
        }

        // Flatten and check duplicates
        $allIds = array_merge($teamA, $teamB);
        $allIds = array_map('intval', $allIds);
        if (count(array_unique($allIds)) !== count($allIds)) {
            return $this->json(['errors' => ['players' => 'Duplicate players are not allowed.']], 400);
        }

        // Ensure all players exist
        $repo = $this->em->getRepository(Player::class);
        /** @var array<int, Player> $playersMap */
        $playersMap = [];
        foreach ($allIds as $pid) {
            $p = $repo->find((int) $pid);
            if (!$p) {
                return $this->json(['errors' => ['players' => 'Unknown player id: '.$pid]], 400);
            }
            $playersMap[$pid] = $p;
        }

        // Persist game and participants
        $game = new Game($type, $targetScore);
        $this->em->persist($game);
        $pos = 1;
        foreach ($teamA as $pid) {
            $this->em->persist(new GameParticipant($game, $playersMap[(int) $pid], 'A', $pos++));
        }
        $pos = 1;
        foreach ($teamB as $pid) {
            $this->em->persist(new GameParticipant($game, $playersMap[(int) $pid], 'B', $pos++));
        }
        $this->em->flush();

        return $this->json(['id' => (int) $game->getId()], 201);
    }
}
