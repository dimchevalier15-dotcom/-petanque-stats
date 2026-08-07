<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Request\CompleteMatchRequest;
use App\Dto\Response\CompleteMatchResponse;
use App\Entity\Game;
use App\Entity\GameBall;
use App\Entity\GameEnd;
use App\Repository\GameParticipantRepository;
use App\Repository\GameRepository;
use App\Repository\PlayerRepository;
use Doctrine\ORM\EntityManagerInterface;

final class MatchRecordingService
{
    public function __construct(
        private GameRepository $games,
        private GameParticipantRepository $participants,
        private PlayerRepository $players,
        private EntityManagerInterface $em,
    ) {
    }

    public function complete(int $matchId, CompleteMatchRequest $req): CompleteMatchResponse
    {
        /** @var Game|null $game */
        $game = $this->games->find($matchId);
        if ($game === null) {
            // Silently return with provided id to avoid changing HTTP contract; nothing to persist
            return new CompleteMatchResponse($matchId);
        }

        // Validate coherence with created match
        $allowedPerPlayer = $req->type === 'triplette' ? 2 : 3;
        $matchPlayerIds = $this->participants->findAllPlayerIdsByGame($game);
        $matchPlayerSet = array_fill_keys($matchPlayerIds, true);

        $tracked = $req->trackedPlayers === [] ? array_merge($req->teamA, $req->teamB) : $req->trackedPlayers;
        $tracked = array_values(array_unique(array_map('intval', $tracked)));
        $trackedSet = array_fill_keys($tracked, true);

        // Start transaction
        $this->em->wrapInTransaction(function () use ($req, $game, $matchPlayerSet, $trackedSet, $allowedPerPlayer): void {
            // Optional: cleanup previous completion if any (idempotency)
            // For minimal change, we do not delete previous data.

            // Preload default shot type map for participants
            $participantRepo = $this->participants;
            $defaultMap = $participantRepo->mapDefaultShotTypeByGame($game);

            foreach ($req->ends as $endDto) {
                // Basic guards
                $isCanceled = property_exists($endDto, 'canceled') ? (bool) $endDto->canceled : false;
                $winner = in_array($endDto->winner, ['A','B'], true) ? $endDto->winner : 'A';
                $points = (int) $endDto->points;
                if ($isCanceled) {
                    // For a canceled end, force points to 0 and do not persist balls
                    $points = 0;
                    $end = new GameEnd($game, $endDto->index, $winner, $points, true);
                    $this->em->persist($end);
                    continue;
                }
                if (!in_array($winner, ['A', 'B'], true)) {
                    continue;
                }
                if ($points <= 0) {
                    continue;
                }
                $end = new GameEnd($game, $endDto->index, $winner, $points, false);
                $this->em->persist($end);

                foreach ($endDto->balls as $ballDto) {
                    $pid = (int) $ballDto->playerId;
                    if (!isset($matchPlayerSet[$pid])) {
                        // ignore balls for players not in this match
                        continue;
                    }
                    if (!isset($trackedSet[$pid])) {
                        // ignore untracked players
                        continue;
                    }
                    $notes = array_values($ballDto->notes);
                    $shots = array_values($ballDto->shotTypes ?? []);
                    $max = min($allowedPerPlayer, count($notes));
                    for ($i = 0; $i < $max; $i++) {
                        $note = (int) $notes[$i];
                        if ($note < -2 || $note > 2) {
                            continue;
                        }
                        $shot = isset($shots[$i]) && in_array($shots[$i], ['point','tir'], true) ? (string) $shots[$i] : ((string) ($defaultMap[$pid] ?? 'point'));
                        $player = $this->players->find($pid);
                        if ($player === null) {
                            continue;
                        }
                        $this->em->persist(new GameBall($end, $player, $i, $note, $shot));
                    }
                }
            }
        });

        return new CompleteMatchResponse((int) $game->getId());
    }
}
