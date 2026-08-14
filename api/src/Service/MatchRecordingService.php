<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Request\CompleteMatchRequest;
use App\Dto\Request\CompleteMatchEndDto;
use App\Dto\Response\CompleteMatchResponse;
use App\Entity\Game;
use App\Entity\GameBall;
use App\Entity\GameEnd;
use App\Entity\GameEndPlayerRole;
use App\Enum\GameType;
use App\Enum\PlayerRole;
use App\Repository\GameEndRepository;
use App\Repository\GameParticipantRepository;
use App\Repository\GameRepository;
use App\Repository\PlayerRepository;
use Doctrine\ORM\EntityManagerInterface;

final class MatchRecordingService
{
    public function __construct(
        private GameRepository $games,
        private GameEndRepository $ends,
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
        $allowedPerPlayer = $req->type === GameType::TRIPLETTE->value ? 2 : 3;
        $matchPlayerIds = $this->participants->findAllPlayerIdsByGame($game);
        $matchPlayerSet = array_fill_keys($matchPlayerIds, true);

        $tracked = $req->trackedPlayers === [] ? array_merge($req->teamA, $req->teamB) : $req->trackedPlayers;
        $tracked = array_values(array_unique(array_map('intval', $tracked)));
        $trackedSet = array_fill_keys($tracked, true);

        $this->em->wrapInTransaction(function () use ($req, $game, $matchPlayerSet, $trackedSet, $allowedPerPlayer): void {
            // Idempotency: replace any previous completion data instead of duplicating it.
            $this->ends->deleteByGame($game);

            // Preload default shot type map for participants
            $participantRepo = $this->participants;
            $defaultMap = $participantRepo->mapDefaultShotTypeByGame($game);

            foreach ($req->ends as $endDto) {
                $isCanceled = property_exists($endDto, 'canceled') ? (bool) $endDto->canceled : false;
                $winner = in_array($endDto->winner, ['A', 'B'], true) ? $endDto->winner : 'A';
                $points = (int) $endDto->points;

                if ($isCanceled) {
                    $points = 0;
                } elseif ($points <= 0) {
                    continue;
                }

                $end = new GameEnd($game, $endDto->index, $winner, $points, $isCanceled);
                $this->em->persist($end);

                $this->persistEndBalls(
                    end: $end,
                    endDto: $endDto,
                    matchPlayerSet: $matchPlayerSet,
                    trackedSet: $trackedSet,
                    allowedPerPlayer: $allowedPerPlayer,
                    defaultMap: $defaultMap,
                );

                $this->persistEndRoles($end, $endDto, $matchPlayerSet);
            }
        });

        return new CompleteMatchResponse((int) $game->getId());
    }

    /**
     * @param array<int, true> $matchPlayerSet
     * @param array<int, true> $trackedSet
     * @param array<int, string> $defaultMap
     */
    private function persistEndBalls(
        GameEnd $end,
        CompleteMatchEndDto $endDto,
        array $matchPlayerSet,
        array $trackedSet,
        int $allowedPerPlayer,
        array $defaultMap,
    ): void {
        foreach ($endDto->balls as $ballDto) {
            $pid = (int) $ballDto->playerId;
            if (!isset($matchPlayerSet[$pid]) || !isset($trackedSet[$pid])) {
                continue;
            }

            $notes = array_values($ballDto->notes);
            $shots = array_values($ballDto->shotTypes ?? []);
            $distances = array_values($ballDto->distances ?? []);
            $max = min($allowedPerPlayer, count($notes));

            for ($i = 0; $i < $max; $i++) {
                $note = (int) $notes[$i];
                if ($note < -2 || $note > 2) {
                    continue;
                }

                $shot = isset($shots[$i]) && in_array($shots[$i], ['point', 'tir'], true)
                    ? (string) $shots[$i]
                    : ((string) ($defaultMap[$pid] ?? 'point'));

                // Distance is purely informational and optional: an invalid value is simply
                // dropped, it never blocks recording the ball itself.
                $distance = null;
                if (isset($distances[$i]) && $distances[$i] !== null && is_numeric($distances[$i])) {
                    $d = (float) $distances[$i];
                    if ($d >= 0) {
                        $distance = $d;
                    }
                }

                $player = $this->players->find($pid);
                if ($player === null) {
                    continue;
                }

                $this->em->persist(new GameBall($end, $player, $i, $note, $shot, $distance));
            }
        }
    }

    /**
     * @param array<int, true> $matchPlayerSet
     */
    private function persistEndRoles(GameEnd $end, CompleteMatchEndDto $endDto, array $matchPlayerSet): void
    {
        foreach ($endDto->roles as $roleDto) {
            $pid = (int) $roleDto->playerId;
            if (!isset($matchPlayerSet[$pid])) {
                continue;
            }

            $role = PlayerRole::tryFrom($roleDto->role);
            if ($role === null) {
                continue;
            }

            $player = $this->players->find($pid);
            if ($player === null) {
                continue;
            }

            $this->em->persist(new GameEndPlayerRole($end, $player, $role));
        }
    }
}
