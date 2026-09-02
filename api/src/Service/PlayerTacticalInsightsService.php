<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Response\MatchInsightsHeldEndErrorResponse;
use App\Dto\Response\MatchInsightsMarkingRateResponse;
use App\Dto\Response\MatchInsightsMarkingTeamResponse;
use App\Dto\Response\MatchInsightsRajoutTeamResponse;
use App\Dto\Response\PlayerTacticalInsightsByDistanceResponse;
use App\Dto\Response\PlayerTacticalInsightsCoverageResponse;
use App\Dto\Response\PlayerTacticalInsightsResponse;
use App\Entity\Game;
use App\Entity\GameBall;
use App\Enum\DistanceBucket;
use App\Enum\GameType;
use App\Enum\MatchNature;
use App\Repository\GameBallRepository;
use App\Repository\GameEndRepository;
use App\Repository\GameParticipantRepository;
use App\Repository\GameRepository;
use App\Repository\GameTrackedRepository;
use App\ValueObject\DateRange;

final class PlayerTacticalInsightsService
{
    public function __construct(
        private PlayerViewContextResolver $playerViewContext,
        private GameRepository $games,
        private GameEndRepository $ends,
        private GameBallRepository $balls,
        private GameParticipantRepository $participants,
        private GameTrackedRepository $tracked,
    ) {
    }

    public function insightsForToken(
        string $token,
        ?MatchNature $nature = null,
        ?DateRange $dateRange = null,
        ?GameType $type = null,
        ?DistanceBucket $distanceBucket = null,
        ?int $competitionId = null,
        ?int $impersonatePlayerId = null,
    ): PlayerTacticalInsightsResponse {
        $context = $this->playerViewContext->resolve($token, $impersonatePlayerId);
        if ($context->playerId === null) {
            return new PlayerTacticalInsightsResponse(status: 'no_player', reason: 'no_player');
        }

        return $this->insightsForPlayerId(
            (int) $context->playerId,
            $nature,
            $dateRange,
            $type,
            $distanceBucket,
            $competitionId,
        );
    }

    public function insightsForPlayerId(
        int $playerId,
        ?MatchNature $nature = null,
        ?DateRange $dateRange = null,
        ?GameType $type = null,
        ?DistanceBucket $distanceBucket = null,
        ?int $competitionId = null,
    ): PlayerTacticalInsightsResponse {
        $games = $this->games->findCompletedGamesForPlayer($playerId, $nature, $dateRange, $type, $competitionId);

        if ($games === []) {
            if ($this->hasDataOutsideFilters($playerId, $nature, $dateRange, $type, $competitionId)) {
                return new PlayerTacticalInsightsResponse(status: 'no_data_in_period', reason: 'no_data_in_period');
            }

            return new PlayerTacticalInsightsResponse(status: 'no_matches', reason: 'no_matches');
        }

        $markingOverall = $this->emptyShotCounters();
        $rajoutOverall = $this->emptyShotCounters();
        $heldEndError = ['minusTwoCount' => 0, 'ballsPlayed' => 0];
        $markingByDistance = [];
        $rajoutByDistance = [];
        $matchesEligible = \count($games);
        $matchesAnalyzed = 0;
        $endsAnalyzed = 0;
        $tacticalAttempts = 0;
        $tacticalAttemptsWithDistance = 0;

        $gameIds = array_map(static fn (Game $game): int => (int) $game->getId(), $games);
        $teamMapsByGame = $this->participants->mapPlayerTeamByGameIds($gameIds);
        $trackedIdsByGame = $this->tracked->findPlayerIdsByGameIds($gameIds);
        $endsByGame = $this->ends->findByGameIdsGrouped($gameIds);
        $ballsByEnd = $this->balls->findByGameIdsGroupedByEnd($gameIds);

        foreach ($games as $game) {
            $gameId = (int) $game->getId();
            $teamMap = $teamMapsByGame[$gameId] ?? [];
            if ($teamMap === [] || !isset($teamMap[$playerId])) {
                continue;
            }

            $participantIds = array_keys($teamMap);
            sort($participantIds);
            $trackedIds = $trackedIdsByGame[$gameId] ?? [];
            if ($participantIds === [] || $participantIds !== $trackedIds) {
                continue;
            }

            $ballsPerPlayer = $this->ballsPerPlayer($game);
            $teamCapacities = $this->teamCapacities($game, $ballsPerPlayer);
            $gameValid = true;
            $gameEndsAnalyzed = 0;

            foreach ($endsByGame[$gameId] ?? [] as $end) {
                if ($end->isCanceled()) {
                    continue;
                }

                $shots = $ballsByEnd[(int) $end->getId()] ?? [];
                if ($shots === []) {
                    continue;
                }

                if (!$this->hasValidSequence($shots, $ballsPerPlayer, $teamMap)) {
                    $gameValid = false;
                    break;
                }

                ++$gameEndsAnalyzed;
                $this->analyzeEndForPlayer(
                    playerId: $playerId,
                    shots: $shots,
                    teamMap: $teamMap,
                    teamCapacities: $teamCapacities,
                    distanceFilter: $distanceBucket,
                    markingOverall: $markingOverall,
                    rajoutOverall: $rajoutOverall,
                    markingByDistance: $markingByDistance,
                    rajoutByDistance: $rajoutByDistance,
                    tacticalAttempts: $tacticalAttempts,
                    tacticalAttemptsWithDistance: $tacticalAttemptsWithDistance,
                    heldEndError: $heldEndError,
                );
            }

            if (!$gameValid || $gameEndsAnalyzed === 0) {
                continue;
            }

            ++$matchesAnalyzed;
            $endsAnalyzed += $gameEndsAnalyzed;
        }

        if ($matchesAnalyzed === 0) {
            return new PlayerTacticalInsightsResponse(
                status: 'no_eligible_matches',
                reason: 'no_eligible_matches',
                coverage: new PlayerTacticalInsightsCoverageResponse(
                    matchesEligible: $matchesEligible,
                    matchesAnalyzed: 0,
                    endsAnalyzed: 0,
                    distanceSampleRate: 0.0,
                ),
            );
        }

        return new PlayerTacticalInsightsResponse(
            status: 'ok',
            markingOverall: $this->toShotTeamResponse($markingOverall),
            rajoutOverall: $this->toRajoutTeamResponse($rajoutOverall),
            heldEndError: $this->toHeldEndErrorResponse($heldEndError),
            markingByDistance: $this->buildByDistanceRows($markingByDistance, $distanceBucket),
            rajoutByDistance: $this->buildByDistanceRows($rajoutByDistance, $distanceBucket),
            coverage: new PlayerTacticalInsightsCoverageResponse(
                matchesEligible: $matchesEligible,
                matchesAnalyzed: $matchesAnalyzed,
                endsAnalyzed: $endsAnalyzed,
                distanceSampleRate: $tacticalAttempts > 0
                    ? round($tacticalAttemptsWithDistance / $tacticalAttempts, 2)
                    : 0.0,
            ),
        );
    }

    /**
     * @param list<GameBall> $shots
     * @param array<int, string> $teamMap
     */
    private function hasValidSequence(array $shots, int $ballsPerPlayer, array $teamMap): bool
    {
        $orders = array_map(static fn (GameBall $ball): int => $ball->getSequenceOrder(), $shots);
        $expected = range(1, \count($orders));

        if ($orders !== $expected) {
            return false;
        }

        $perPlayer = [];
        foreach ($shots as $shot) {
            $pid = (int) $shot->getPlayer()->getId();
            if (!isset($teamMap[$pid])) {
                return false;
            }
            $perPlayer[$pid] = ($perPlayer[$pid] ?? 0) + 1;
            if ($perPlayer[$pid] > $ballsPerPlayer) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param list<GameBall> $shots
     * @param array<int, string> $teamMap
     * @param array{A:int,B:int} $teamCapacities
     * @param array{point:array{made:int,attempts:int},tir:array{made:int,attempts:int}} $markingOverall
     * @param array{point:array{made:int,attempts:int},tir:array{made:int,attempts:int}} $rajoutOverall
     * @param array<string, array{point:array{made:int,attempts:int},tir:array{made:int,attempts:int}}>> $markingByDistance
     * @param array<string, array{point:array{made:int,attempts:int},tir:array{made:int,attempts:int}}>> $rajoutByDistance
     * @param array{minusTwoCount:int,ballsPlayed:int} $heldEndError
     */
    private function analyzeEndForPlayer(
        int $playerId,
        array $shots,
        array $teamMap,
        array $teamCapacities,
        ?DistanceBucket $distanceFilter,
        array &$markingOverall,
        array &$rajoutOverall,
        array &$markingByDistance,
        array &$rajoutByDistance,
        int &$tacticalAttempts,
        int &$tacticalAttemptsWithDistance,
        array &$heldEndError,
    ): void {
        $playedByTeam = ['A' => 0, 'B' => 0];
        $markingTeam = null;
        $markingActive = false;
        $rajoutActive = false;
        $rajoutTeam = null;

        foreach ($shots as $shot) {
            $team = $teamMap[(int) $shot->getPlayer()->getId()];
            $opponent = $team === 'A' ? 'B' : 'A';
            $note = $shot->getNote();
            $shotType = $shot->getShotType();
            $markSuccess = false;

            if ($markingActive && $team === $markingTeam && !$shot->isCochonnet() && \in_array($shotType, ['point', 'tir'], true)) {
                $this->recordPlayerTacticalShot(
                    playerId: $playerId,
                    shot: $shot,
                    overall: $markingOverall,
                    byDistance: $markingByDistance,
                    distanceFilter: $distanceFilter,
                    tacticalAttempts: $tacticalAttempts,
                    tacticalAttemptsWithDistance: $tacticalAttemptsWithDistance,
                );
                if ($note >= 1) {
                    $markingActive = false;
                    $markSuccess = true;
                }
            }

            if ($rajoutActive && $team === $rajoutTeam && !$shot->isCochonnet() && \in_array($shotType, ['point', 'tir'], true)) {
                if ((int) $shot->getPlayer()->getId() === $playerId) {
                    $this->recordPlayerTacticalShot(
                        playerId: $playerId,
                        shot: $shot,
                        overall: $rajoutOverall,
                        byDistance: $rajoutByDistance,
                        distanceFilter: $distanceFilter,
                        tacticalAttempts: $tacticalAttempts,
                        tacticalAttemptsWithDistance: $tacticalAttemptsWithDistance,
                    );
                }
                if ($note === -2) {
                    $rajoutActive = false;
                }
            }

            $opponentRemaining = $teamCapacities[$opponent] - $playedByTeam[$opponent];
            if (
                $opponentRemaining <= 0
                && !$shot->isCochonnet()
                && \in_array($shotType, ['point', 'tir'], true)
                && (int) $shot->getPlayer()->getId() === $playerId
            ) {
                ++$heldEndError['ballsPlayed'];
                if ($note === -2) {
                    ++$heldEndError['minusTwoCount'];
                }
            }

            ++$playedByTeam[$team];

            $teamRemaining = $teamCapacities[$team] - $playedByTeam[$team];
            $opponentRemaining = $teamCapacities[$opponent] - $playedByTeam[$opponent];

            if ($markSuccess && $teamRemaining > 0) {
                $rajoutActive = true;
                $rajoutTeam = $markingTeam;
            }

            if ($markingTeam === null && !$rajoutActive && $teamRemaining <= 0 && $opponentRemaining > 0) {
                if ($note <= 0) {
                    $rajoutActive = true;
                    $rajoutTeam = $opponent;
                } else {
                    $markingTeam = $opponent;
                    $markingActive = true;
                }
            }
        }
    }

    /**
     * @param array{point:array{made:int,attempts:int},tir:array{made:int,attempts:int}} $overall
     * @param array<string, array{point:array{made:int,attempts:int},tir:array{made:int,attempts:int}}>> $byDistance
     */
    private function recordPlayerTacticalShot(
        int $playerId,
        GameBall $shot,
        array &$overall,
        array &$byDistance,
        ?DistanceBucket $distanceFilter,
        int &$tacticalAttempts,
        int &$tacticalAttemptsWithDistance,
    ): void {
        if ((int) $shot->getPlayer()->getId() !== $playerId) {
            return;
        }

        $shotType = $shot->getShotType();
        if (!\in_array($shotType, ['point', 'tir'], true)) {
            return;
        }

        $distance = $shot->getDistance();
        $bucket = $distance !== null ? DistanceBucket::fromDistance($distance)?->value : null;

        if ($distanceFilter !== null) {
            if ($bucket !== $distanceFilter->value) {
                return;
            }
        }

        ++$tacticalAttempts;
        if ($distance !== null) {
            ++$tacticalAttemptsWithDistance;
        }

        ++$overall[$shotType]['attempts'];
        if ($shot->getNote() >= 1) {
            ++$overall[$shotType]['made'];
        }

        if ($bucket === null) {
            return;
        }

        if (!isset($byDistance[$bucket])) {
            $byDistance[$bucket] = $this->emptyShotCounters();
        }

        ++$byDistance[$bucket][$shotType]['attempts'];
        if ($shot->getNote() >= 1) {
            ++$byDistance[$bucket][$shotType]['made'];
        }
    }

    /**
     * @param array<string, array{point:array{made:int,attempts:int},tir:array{made:int,attempts:int}}>> $byDistance
     *
     * @return list<PlayerTacticalInsightsByDistanceResponse>
     */
    private function buildByDistanceRows(array $byDistance, ?DistanceBucket $distanceFilter): array
    {
        $rows = [];

        foreach (DistanceBucket::cases() as $bucket) {
            if ($distanceFilter !== null && $bucket !== $distanceFilter) {
                continue;
            }

            $key = $bucket->value;
            if (!isset($byDistance[$key])) {
                continue;
            }

            $counters = $byDistance[$key];
            if ($counters['point']['attempts'] === 0 && $counters['tir']['attempts'] === 0) {
                continue;
            }

            $rows[] = new PlayerTacticalInsightsByDistanceResponse(
                bucket: $key,
                point: $this->toRateResponse($counters['point']),
                tir: $this->toRateResponse($counters['tir']),
            );
        }

        return $rows;
    }

    /**
     * @param array{made:int,attempts:int} $counters
     */
    private function toRateResponse(array $counters): MatchInsightsMarkingRateResponse
    {
        $attempts = $counters['attempts'];

        return new MatchInsightsMarkingRateResponse(
            made: $counters['made'],
            attempts: $attempts,
            rate: $attempts > 0 ? round($counters['made'] / $attempts * 100, 1) : null,
        );
    }

    /**
     * @param array{point:array{made:int,attempts:int},tir:array{made:int,attempts:int}} $stats
     */
    private function toShotTeamResponse(array $stats): MatchInsightsMarkingTeamResponse
    {
        return new MatchInsightsMarkingTeamResponse(
            point: $this->toRateResponse($stats['point']),
            tir: $this->toRateResponse($stats['tir']),
        );
    }

    /**
     * @param array{point:array{made:int,attempts:int},tir:array{made:int,attempts:int}} $stats
     */
    private function toRajoutTeamResponse(array $stats): MatchInsightsRajoutTeamResponse
    {
        return new MatchInsightsRajoutTeamResponse(
            point: $this->toRateResponse($stats['point']),
            tir: $this->toRateResponse($stats['tir']),
        );
    }

    /**
     * @param array{minusTwoCount:int,ballsPlayed:int} $counters
     */
    private function toHeldEndErrorResponse(array $counters): MatchInsightsHeldEndErrorResponse
    {
        $ballsPlayed = $counters['ballsPlayed'];

        return new MatchInsightsHeldEndErrorResponse(
            minusTwoCount: $counters['minusTwoCount'],
            ballsPlayed: $ballsPlayed,
            rate: $ballsPlayed > 0 ? round($counters['minusTwoCount'] / $ballsPlayed * 100, 1) : null,
        );
    }

    /**
     * @return array{point:array{made:int,attempts:int},tir:array{made:int,attempts:int}}
     */
    private function emptyShotCounters(): array
    {
        return [
            'point' => ['made' => 0, 'attempts' => 0],
            'tir' => ['made' => 0, 'attempts' => 0],
        ];
    }

    private function ballsPerPlayer(Game $game): int
    {
        return $game->getType() === GameType::TRIPLETTE ? 2 : 3;
    }

    /**
     * @return array{A:int,B:int}
     */
    private function teamCapacities(Game $game, int $ballsPerPlayer): array
    {
        $type = $game->getType();

        return [
            'A' => $type->playersPerTeam() * $ballsPerPlayer,
            'B' => $type->playersPerTeam() * $ballsPerPlayer,
        ];
    }

    private function hasDataOutsideFilters(
        int $playerId,
        ?MatchNature $nature,
        ?DateRange $dateRange,
        ?GameType $type,
        ?int $competitionId,
    ): bool {
        if ($nature === null && $dateRange === null && $type === null && $competitionId === null) {
            return false;
        }

        return $this->games->findCompletedGamesForPlayer($playerId) !== [];
    }
}
