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
use App\Entity\GameParticipant;
use App\Entity\GameTracked;
use App\Entity\Player;
use App\Enum\GameType;
use App\Enum\PlayerRole;
use App\Repository\GameEndRepository;
use App\Repository\GameParticipantRepository;
use App\Repository\GameRepository;
use App\Repository\GameTrackedRepository;
use App\Repository\PlayerRepository;
use Doctrine\ORM\EntityManagerInterface;

final class MatchRecordingService
{
    public function __construct(
        private GameRepository $games,
        private GameEndRepository $ends,
        private GameParticipantRepository $participants,
        private GameTrackedRepository $trackedPlayers,
        private PlayerRepository $players,
        private EntityManagerInterface $em,
        private GameParticipantValidationResolver $validationResolver,
        private MatchShareService $share,
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
        $maxPointsPerEnd = GameType::tryFrom($req->type)?->maxPointsPerEnd() ?? 6;
        $matchPlayerIds = $this->participants->findAllPlayerIdsByGame($game);
        $matchPlayerSet = array_fill_keys($matchPlayerIds, true);

        $tracked = $req->trackedPlayers === [] ? array_merge($req->teamA, $req->teamB) : $req->trackedPlayers;
        $tracked = $this->players->filterPlaceholderIds(array_values(array_unique(array_map('intval', $tracked))));
        $trackedSet = array_fill_keys($tracked, true);

        $this->em->wrapInTransaction(function () use ($req, $game, $matchPlayerSet, $trackedSet, $tracked, $allowedPerPlayer, $maxPointsPerEnd): void {
            $game->setOpeningScoreA(max(0, $req->openingScoreA));
            $game->setOpeningScoreB(max(0, $req->openingScoreB));

            // Idempotency: replace any previous completion data instead of duplicating it.
            $this->ends->deleteByGame($game);
            $this->syncTrackedPlayers($game, $tracked);

            $this->registerSubstitutions($game, $req, $matchPlayerSet, $trackedSet);

            // Preload default shot type map for participants
            $participantRepo = $this->participants;
            $defaultMap = $participantRepo->mapDefaultShotTypeByGame($game);

            foreach ($req->ends as $endDto) {
                $isCanceled = property_exists($endDto, 'canceled') ? (bool) $endDto->canceled : false;
                $winner = in_array($endDto->winner, ['A', 'B'], true) ? $endDto->winner : 'A';
                $points = (int) $endDto->points;

                if ($isCanceled) {
                    $points = 0;
                } elseif ($points < 0) {
                    continue;
                } else {
                    $points = min($points, $maxPointsPerEnd);
                }

                $end = new GameEnd($game, $endDto->index, $winner, $points, $isCanceled);
                $this->em->persist($end);

                $this->persistEndShots(
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

        $shareUuid = $this->share->ensureShareUuid($game);

        return new CompleteMatchResponse((int) $game->getId(), $shareUuid);
    }

    /**
     * @param list<int> $trackedPlayerIds
     */
    private function syncTrackedPlayers(Game $game, array $trackedPlayerIds): void
    {
        $this->trackedPlayers->deleteByGame($game);

        foreach ($trackedPlayerIds as $playerId) {
            $player = $this->players->find((int) $playerId);
            if ($player === null) {
                continue;
            }

            $this->em->persist(new GameTracked($game, $player));
        }
    }

    /**
     * @param array<int, true> $matchPlayerSet
     * @param array<int, true> $trackedSet
     */
    private function registerSubstitutions(
        Game $game,
        CompleteMatchRequest $req,
        array &$matchPlayerSet,
        array &$trackedSet,
    ): void {
        $substitutionsByTeam = [];
        foreach ($req->substitutions as $subDto) {
            $team = in_array($subDto->team, ['A', 'B'], true) ? $subDto->team : null;
            if ($team === null) {
                continue;
            }
            if (isset($substitutionsByTeam[$team])) {
                continue;
            }

            $outId = (int) $subDto->outPlayerId;
            $inId = (int) $subDto->inPlayerId;
            if ($outId <= 0 || $inId <= 0 || $outId === $inId) {
                continue;
            }
            if (isset($matchPlayerSet[$inId])) {
                continue;
            }

            $teamIds = array_map('intval', $team === 'A' ? $req->teamA : $req->teamB);
            $positionIndex = array_search($outId, $teamIds, true);
            if ($positionIndex === false) {
                continue;
            }

            $outPlayer = $this->players->find($outId);
            if ($outPlayer === null) {
                continue;
            }

            $player = $this->players->find($inId);
            if ($player === null) {
                continue;
            }

            $outParticipant = $this->participants->findOneBy(['game' => $game, 'player' => $outPlayer]);
            if ($outParticipant === null) {
                continue;
            }

            $creator = $game->getCreatedBy();
            $validated = $creator !== null
                ? $this->validationResolver->resolveInitialValue($player, $creator)
                : true;

            $this->em->persist(new GameParticipant(
                $game,
                $player,
                $team,
                $positionIndex + 1,
                $outParticipant->getDefaultShotType(),
                $outParticipant->getStartingRole(),
                $validated,
            ));
            $matchPlayerSet[$inId] = true;
            $substitutionsByTeam[$team] = true;

            if (isset($trackedSet[$outId])) {
                $this->em->persist(new GameTracked($game, $player));
                $trackedSet[$inId] = true;
            }
        }
    }

    /**
     * @param array<int, true> $matchPlayerSet
     * @param array<int, true> $trackedSet
     * @param array<int, string> $defaultMap
     */
    private function persistEndShots(
        GameEnd $end,
        CompleteMatchEndDto $endDto,
        array $matchPlayerSet,
        array $trackedSet,
        int $allowedPerPlayer,
        array $defaultMap,
    ): void {
        $shots = $this->normalizeEndShots($endDto, $matchPlayerSet, $trackedSet, $allowedPerPlayer, $defaultMap);

        foreach ($shots as $shot) {
            $player = $this->players->find($shot['playerId']);
            if ($player === null) {
                continue;
            }

            $this->em->persist(new GameBall(
                $end,
                $player,
                $shot['sequenceOrder'],
                $shot['note'],
                $shot['shotType'],
                $shot['distance'],
                $shot['isCochonnet'],
            ));
        }
    }

    /**
     * @param array<int, true> $matchPlayerSet
     * @param array<int, true> $trackedSet
     * @param array<int, string> $defaultMap
     *
     * @return list<array{sequenceOrder:int,playerId:int,note:int,shotType:string,distance:?float,isCochonnet:bool}>
     */
    private function normalizeEndShots(
        CompleteMatchEndDto $endDto,
        array $matchPlayerSet,
        array $trackedSet,
        int $allowedPerPlayer,
        array $defaultMap,
    ): array {
        if ($endDto->shots !== []) {
            $shots = [];
            foreach ($endDto->shots as $shotDto) {
                $pid = (int) $shotDto->playerId;
                if (!isset($matchPlayerSet[$pid]) || !isset($trackedSet[$pid])) {
                    continue;
                }

                $note = (int) $shotDto->note;
                if ($note < -2 || $note > 2) {
                    continue;
                }

                $shotType = in_array($shotDto->shotType, ['point', 'tir'], true)
                    ? $shotDto->shotType
                    : (string) ($defaultMap[$pid] ?? 'point');

                $distance = null;
                if ($shotDto->distance !== null && $shotDto->distance >= 0) {
                    $distance = (float) $shotDto->distance;
                }

                $shots[] = [
                    'sequenceOrder' => (int) $shotDto->sequenceOrder,
                    'playerId' => $pid,
                    'note' => $note,
                    'shotType' => $shotType,
                    'distance' => $distance,
                    'isCochonnet' => $shotDto->isCochonnet === true,
                ];
            }

            usort($shots, static fn (array $a, array $b): int => $a['sequenceOrder'] <=> $b['sequenceOrder']);

            return $shots;
        }

        // Legacy per-player balls payload (invalid for tactical insights, still stored sequentially).
        $sequenceOrder = 1;
        $shots = [];
        foreach ($endDto->balls as $ballDto) {
            $pid = (int) $ballDto->playerId;
            if (!isset($matchPlayerSet[$pid]) || !isset($trackedSet[$pid])) {
                continue;
            }

            $notes = array_values($ballDto->notes);
            $shotTypes = array_values($ballDto->shotTypes ?? []);
            $distances = array_values($ballDto->distances ?? []);
            $cochonnetFlags = array_values($ballDto->isCochonnet ?? []);
            $max = min($allowedPerPlayer, count($notes));

            for ($i = 0; $i < $max; $i++) {
                $note = (int) $notes[$i];
                if ($note < -2 || $note > 2) {
                    continue;
                }

                $shot = isset($shotTypes[$i]) && in_array($shotTypes[$i], ['point', 'tir'], true)
                    ? (string) $shotTypes[$i]
                    : ((string) ($defaultMap[$pid] ?? 'point'));

                $distance = null;
                if (isset($distances[$i]) && $distances[$i] !== null && is_numeric($distances[$i])) {
                    $d = (float) $distances[$i];
                    if ($d >= 0) {
                        $distance = $d;
                    }
                }

                $isCochonnet = isset($cochonnetFlags[$i]) && $cochonnetFlags[$i] === true;

                $shots[] = [
                    'sequenceOrder' => $sequenceOrder,
                    'playerId' => $pid,
                    'note' => $note,
                    'shotType' => $shot,
                    'distance' => $distance,
                    'isCochonnet' => $isCochonnet,
                ];
                ++$sequenceOrder;
            }
        }

        return $shots;
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
