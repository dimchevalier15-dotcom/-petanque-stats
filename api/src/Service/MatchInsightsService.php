<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Response\MatchInsightsByDistanceResponse;
use App\Dto\Response\MatchInsightsCoverageResponse;
use App\Dto\Response\MatchInsightsDistanceOutlookResponse;
use App\Dto\Response\MatchInsightsDistanceTeamResponse;
use App\Dto\Response\MatchInsightsMarkingRateResponse;
use App\Dto\Response\MatchInsightsMarkingTeamResponse;
use App\Dto\Response\MatchInsightsPointDominanceResponse;
use App\Dto\Response\MatchInsightsRajoutTeamResponse;
use App\Dto\Response\MatchInsightsResponse;
use App\Dto\Response\MatchInsightsTeamResponse;
use App\Entity\Game;
use App\Entity\GameBall;
use App\Entity\GameEnd;
use App\Enum\DistanceBucket;
use App\Enum\GameType;
use App\Repository\GameBallRepository;
use App\Repository\GameEndRepository;
use App\Repository\GameParticipantRepository;
use App\Repository\GameRepository;
use App\Repository\GameTrackedRepository;

final class MatchInsightsService
{
    private const DOMINANCE_AVG_GAP = 0.5;
    private const DOMINANCE_MIN_BALLS = 3;
    private const LIMITED_DAMAGE_MAX_POINTS = 2;

    public function __construct(
        private GameRepository $games,
        private GameEndRepository $ends,
        private GameBallRepository $balls,
        private GameParticipantRepository $participants,
        private GameTrackedRepository $tracked,
        private MatchSuccessRateCalculator $successRate,
    ) {
    }

    public function getInsights(int $matchId): ?MatchInsightsResponse
    {
        $game = $this->games->find($matchId);
        if ($game === null) {
            return null;
        }

        if (!$this->allPlayersTracked($game)) {
            return new MatchInsightsResponse(
                status: 'unavailable',
                reason: 'not_all_tracked',
            );
        }

        $teamMap = $this->participants->mapPlayerTeamByGame($game);
        $ballsPerPlayer = $this->ballsPerPlayer($game);
        $teamCapacities = $this->teamCapacities($game, $ballsPerPlayer);

        $ends = $this->ends->findBy(['game' => $game], ['index' => 'ASC']);
        if ($ends === []) {
            return new MatchInsightsResponse(
                status: 'unavailable',
                reason: 'no_data',
            );
        }

        $teamStats = [
            'A' => $this->emptyTeamStats('A'),
            'B' => $this->emptyTeamStats('B'),
        ];
        $markingStats = $this->emptyMarkingStats();
        $rajoutStats = $this->emptyMarkingStats();
        $distanceAgg = [];
        $totalBalls = 0;
        $ballsWithDistance = 0;
        $endsAnalyzed = 0;

        foreach ($ends as $end) {
            if ($end->isCanceled()) {
                continue;
            }

            $shots = $this->balls->findByEndOrdered($end);
            if ($shots === []) {
                continue;
            }

            if (!$this->hasValidSequence($shots, $ballsPerPlayer, $teamMap)) {
                return new MatchInsightsResponse(
                    status: 'unavailable',
                    reason: 'invalid_sequence',
                );
            }

            ++$endsAnalyzed;
            $this->analyzeEnd(
                end: $end,
                shots: $shots,
                teamMap: $teamMap,
                teamCapacities: $teamCapacities,
                teamStats: $teamStats,
                markingStats: $markingStats,
                rajoutStats: $rajoutStats,
                distanceAgg: $distanceAgg,
                totalBalls: $totalBalls,
                ballsWithDistance: $ballsWithDistance,
            );
        }

        if ($endsAnalyzed === 0) {
            return new MatchInsightsResponse(
                status: 'unavailable',
                reason: 'no_data',
            );
        }

        return new MatchInsightsResponse(
            status: 'ok',
            teamA: $this->toTeamResponse('A', $teamStats['A']),
            teamB: $this->toTeamResponse('B', $teamStats['B']),
            markingTeamA: $this->toMarkingTeamResponse($markingStats['A']),
            markingTeamB: $this->toMarkingTeamResponse($markingStats['B']),
            rajoutTeamA: $this->toRajoutTeamResponse($rajoutStats['A']),
            rajoutTeamB: $this->toRajoutTeamResponse($rajoutStats['B']),
            pointDominanceTeamA: new MatchInsightsPointDominanceResponse(
                $teamStats['A']['endsWonWhenOpened'],
                $teamStats['A']['endsOpened'],
            ),
            pointDominanceTeamB: new MatchInsightsPointDominanceResponse(
                $teamStats['B']['endsWonWhenOpened'],
                $teamStats['B']['endsOpened'],
            ),
            distanceOutlook: $this->buildDistanceOutlook($distanceAgg),
            coverage: new MatchInsightsCoverageResponse(
                distanceSampleRate: $totalBalls > 0 ? round($ballsWithDistance / $totalBalls, 2) : 0.0,
                endsAnalyzed: $endsAnalyzed,
            ),
        );
    }

    private function allPlayersTracked(Game $game): bool
    {
        $participantIds = $this->participants->findAllPlayerIdsByGame($game);
        $trackedIds = $this->tracked->findPlayerIdsByGame($game);

        sort($participantIds);
        sort($trackedIds);

        return $participantIds !== [] && $participantIds === $trackedIds;
    }

    /**
     * @param list<GameBall> $shots
     * @param array<int, string> $teamMap
     */
    private function hasValidSequence(array $shots, int $ballsPerPlayer, array $teamMap): bool
    {
        $orders = array_map(static fn (GameBall $ball): int => $ball->getSequenceOrder(), $shots);
        $expected = range(1, count($orders));

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
     * @param array{A:array<string,mixed>,B:array<string,mixed>} $teamStats
     * @param array{A:array{point:array{made:int,attempts:int},tir:array{made:int,attempts:int}},B:array{point:array{made:int,attempts:int},tir:array{made:int,attempts:int}}} $markingStats
     * @param array{A:array{point:array{made:int,attempts:int},tir:array{made:int,attempts:int}},B:array{point:array{made:int,attempts:int},tir:array{made:int,attempts:int}}} $rajoutStats
     * @param array<string, array{A: array{sum:int,count:int,p2:int,p1:int,p0:int,m1:int,m2:int}, B: array{sum:int,count:int,p2:int,p1:int,p0:int,m1:int,m2:int}>> $distanceAgg
     */
    private function analyzeEnd(
        GameEnd $end,
        array $shots,
        array $teamMap,
        array $teamCapacities,
        array &$teamStats,
        array &$markingStats,
        array &$rajoutStats,
        array &$distanceAgg,
        int &$totalBalls,
        int &$ballsWithDistance,
    ): void {
        $firstShot = $shots[0];
        $firstTeam = $teamMap[(int) $firstShot->getPlayer()->getId()];
        ++$teamStats[$firstTeam]['endsOpened'];
        $teamStats[$firstTeam]['firstShotSum'] += $firstShot->getNote();
        ++$teamStats[$firstTeam]['firstShotCount'];

        $winner = $end->getWinner();
        if ($winner === 'A' || $winner === 'B') {
            ++$teamStats[$winner]['endsWon'];
            if ($winner === $firstTeam) {
                ++$teamStats[$firstTeam]['endsWonWhenOpened'];
            }
        }

        $pointHolder = $firstTeam;
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

            if ($markingActive && $team === $markingTeam && !$shot->isCochonnet() && in_array($shotType, ['point', 'tir'], true)) {
                ++$markingStats[$team][$shotType]['attempts'];
                if ($note >= 1) {
                    ++$markingStats[$team][$shotType]['made'];
                    $markingActive = false;
                    $markSuccess = true;
                }
            }

            if ($rajoutActive && $team === $rajoutTeam && !$shot->isCochonnet() && in_array($shotType, ['point', 'tir'], true)) {
                ++$rajoutStats[$team][$shotType]['attempts'];
                if ($note >= 1) {
                    ++$rajoutStats[$team][$shotType]['made'];
                }
                if ($note === -2) {
                    $rajoutActive = false;
                }
            }

            if ($shotType === 'tir' && !$shot->isCochonnet() && $note >= 1) {
                ++$teamStats[$team]['reclaimsCount'];
                $pointHolder = $team;
            } elseif ($shotType === 'point' && $note >= 1 && $pointHolder !== $team) {
                $pointHolder = $team;
            }

            ++$playedByTeam[$team];
            ++$totalBalls;

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

            $distance = $shot->getDistance();
            if ($distance !== null) {
                ++$ballsWithDistance;
                $bucket = DistanceBucket::fromDistance($distance)?->value;
                if ($bucket !== null) {
                    $this->accumulateDistance($distanceAgg, $bucket, $team, $note, $shotType, $shot->isCochonnet());
                }
            }
        }

        $remaining = [
            'A' => $teamCapacities['A'] - $playedByTeam['A'],
            'B' => $teamCapacities['B'] - $playedByTeam['B'],
        ];

        $this->analyzeClosing('A', 'B', $remaining, $winner, $end->getPoints(), $teamStats);
        $this->analyzeClosing('B', 'A', $remaining, $winner, $end->getPoints(), $teamStats);
    }

    /**
     * @param array{A:int,B:int} $remaining
     * @param array{A:array<string,mixed>,B:array<string,mixed>} $teamStats
     */
    private function analyzeClosing(
        string $teamWithBalls,
        string $teamOut,
        array $remaining,
        string $winner,
        int $points,
        array &$teamStats,
    ): void {
        if ($remaining[$teamWithBalls] <= 0 || $remaining[$teamOut] > 0) {
            return;
        }

        ++$teamStats[$teamWithBalls]['capitalizationOpportunities'];
        if ($winner === $teamWithBalls) {
            ++$teamStats[$teamWithBalls]['capitalizedCount'];
            $teamStats[$teamWithBalls]['capitalizedPointsSum'] += $points;
        }

        ++$teamStats[$teamOut]['defenseSituations'];
        if ($winner === $teamOut || $points <= self::LIMITED_DAMAGE_MAX_POINTS) {
            ++$teamStats[$teamOut]['defendedCount'];
            $teamStats[$teamOut]['defensePointsSum'] += $points;
        }
    }

    /**
     * @param array<string, array{A: array{sum:int,count:int,p2:int,p1:int,p0:int,m1:int,m2:int}, B: array{sum:int,count:int,p2:int,p1:int,p0:int,m1:int,m2:int}>> $distanceAgg
     */
    private function accumulateDistance(
        array &$distanceAgg,
        string $bucket,
        string $team,
        int $note,
        string $shotType,
        bool $isCochonnet,
    ): void {
        if (!isset($distanceAgg[$bucket])) {
            $distanceAgg[$bucket] = [
                'A' => ['sum' => 0, 'count' => 0, 'p2' => 0, 'p1' => 0, 'p0' => 0, 'm1' => 0, 'm2' => 0, 'pointCount' => 0, 'pointP2' => 0, 'pointP1' => 0],
                'B' => ['sum' => 0, 'count' => 0, 'p2' => 0, 'p1' => 0, 'p0' => 0, 'm1' => 0, 'm2' => 0, 'pointCount' => 0, 'pointP2' => 0, 'pointP1' => 0],
            ];
        }

        $distanceAgg[$bucket][$team]['sum'] += $note;
        ++$distanceAgg[$bucket][$team]['count'];

        match (true) {
            $note === 2 => ++$distanceAgg[$bucket][$team]['p2'],
            $note === 1 => ++$distanceAgg[$bucket][$team]['p1'],
            $note === 0 => ++$distanceAgg[$bucket][$team]['p0'],
            $note === -1 => ++$distanceAgg[$bucket][$team]['m1'],
            default => ++$distanceAgg[$bucket][$team]['m2'],
        };

        if ($shotType === 'point' && !$isCochonnet) {
            ++$distanceAgg[$bucket][$team]['pointCount'];
            if ($note === 2) {
                ++$distanceAgg[$bucket][$team]['pointP2'];
            } elseif ($note === 1) {
                ++$distanceAgg[$bucket][$team]['pointP1'];
            }
        }
    }

    /**
     * @param array<string, array{A: array{sum:int,count:int,p2:int,p1:int,p0:int,m1:int,m2:int,pointCount:int,pointP2:int,pointP1:int}, B: array{sum:int,count:int,p2:int,p1:int,p0:int,m1:int,m2:int,pointCount:int,pointP2:int,pointP1:int}>> $distanceAgg
     *
     * @return list<MatchInsightsByDistanceResponse>
     */
    private function buildByDistance(array $distanceAgg): array
    {
        $rows = [];
        foreach (DistanceBucket::cases() as $bucket) {
            $key = $bucket->value;
            if (!isset($distanceAgg[$key])) {
                continue;
            }

            $teamA = $this->distanceTeamResponse($distanceAgg[$key]['A']);
            $teamB = $this->distanceTeamResponse($distanceAgg[$key]['B']);
            if ($teamA === null && $teamB === null) {
                continue;
            }

            $dominant = null;
            if ($teamA !== null && $teamB !== null
                && $teamA->balls >= self::DOMINANCE_MIN_BALLS
                && $teamB->balls >= self::DOMINANCE_MIN_BALLS
            ) {
                $gap = $teamA->average - $teamB->average;
                if ($gap >= self::DOMINANCE_AVG_GAP) {
                    $dominant = 'A';
                } elseif ($gap <= -self::DOMINANCE_AVG_GAP) {
                    $dominant = 'B';
                }
            }

            $rows[] = new MatchInsightsByDistanceResponse(
                bucket: $key,
                teamA: $teamA,
                teamB: $teamB,
                dominantTeam: $dominant,
            );
        }

        return $rows;
    }

    /**
     * @param array<string, array{A: array{sum:int,count:int,p2:int,p1:int,p0:int,m1:int,m2:int,pointCount:int,pointP2:int,pointP1:int}, B: array{sum:int,count:int,p2:int,p1:int,p0:int,m1:int,m2:int,pointCount:int,pointP2:int,pointP1:int}>> $distanceAgg
     */
    private function buildDistanceOutlook(array $distanceAgg): MatchInsightsDistanceOutlookResponse
    {
        $byDistance = $this->buildByDistance($distanceAgg);
        $dominantRows = array_values(array_filter(
            $byDistance,
            static fn (MatchInsightsByDistanceResponse $row): bool => $row->dominantTeam !== null,
        ));

        if ($dominantRows === []) {
            return new MatchInsightsDistanceOutlookResponse(null, []);
        }

        $teams = array_values(array_unique(array_map(
            static fn (MatchInsightsByDistanceResponse $row): string => (string) $row->dominantTeam,
            $dominantRows,
        )));

        if (\count($teams) === 1) {
            return new MatchInsightsDistanceOutlookResponse($teams[0], []);
        }

        return new MatchInsightsDistanceOutlookResponse(null, $dominantRows);
    }

    /**
     * @param array{sum:int,count:int,p2:int,p1:int,p0:int,m1:int,m2:int,pointCount:int,pointP2:int,pointP1:int} $agg
     */
    private function distanceTeamResponse(array $agg): ?MatchInsightsDistanceTeamResponse
    {
        if ($agg['count'] === 0) {
            return null;
        }

        return new MatchInsightsDistanceTeamResponse(
            average: round($agg['sum'] / $agg['count'], 2),
            balls: $agg['count'],
            pointSuccessRate: $this->successRate->fromNoteCounts(
                $agg['pointP2'],
                $agg['pointP1'],
                0,
                0,
                0,
            ),
        );
    }

    /**
     * @param array{point:array{made:int,attempts:int},tir:array{made:int,attempts:int}} $stats
     */
    private function toMarkingTeamResponse(array $stats): MatchInsightsMarkingTeamResponse
    {
        return new MatchInsightsMarkingTeamResponse(
            point: $this->toMarkingRateResponse($stats['point']),
            tir: $this->toMarkingRateResponse($stats['tir']),
        );
    }

    /**
     * @param array{point:array{made:int,attempts:int},tir:array{made:int,attempts:int}} $stats
     */
    private function toRajoutTeamResponse(array $stats): MatchInsightsRajoutTeamResponse
    {
        return new MatchInsightsRajoutTeamResponse(
            point: $this->toMarkingRateResponse($stats['point']),
            tir: $this->toMarkingRateResponse($stats['tir']),
        );
    }

    /**
     * @param array{made:int,attempts:int} $counters
     */
    private function toMarkingRateResponse(array $counters): MatchInsightsMarkingRateResponse
    {
        $attempts = $counters['attempts'];

        return new MatchInsightsMarkingRateResponse(
            made: $counters['made'],
            attempts: $attempts,
            rate: $attempts > 0 ? round($counters['made'] / $attempts * 100, 1) : null,
        );
    }

    /**
     * @param array<string, mixed> $stats
     */
    private function toTeamResponse(string $team, array $stats): MatchInsightsTeamResponse
    {
        return new MatchInsightsTeamResponse(
            team: $team,
            endsWon: $stats['endsWon'],
            endsOpened: $stats['endsOpened'],
            firstShotAverage: $stats['firstShotCount'] > 0
                ? round($stats['firstShotSum'] / $stats['firstShotCount'], 2)
                : 0.0,
            capitalizedCount: $stats['capitalizedCount'],
            capitalizationOpportunities: $stats['capitalizationOpportunities'],
            avgPointsWhenCapitalizing: $stats['capitalizedCount'] > 0
                ? round($stats['capitalizedPointsSum'] / $stats['capitalizedCount'], 2)
                : 0.0,
            defendedCount: $stats['defendedCount'],
            defenseSituations: $stats['defenseSituations'],
            avgPointsConcededWhenDefending: $stats['defendedCount'] > 0
                ? round($stats['defensePointsSum'] / $stats['defendedCount'], 2)
                : 0.0,
            reclaimsCount: $stats['reclaimsCount'],
        );
    }

    /**
     * @return array{A:array{point:array{made:int,attempts:int},tir:array{made:int,attempts:int}},B:array{point:array{made:int,attempts:int},tir:array{made:int,attempts:int}}}
     */
    private function emptyMarkingStats(): array
    {
        $empty = [
            'point' => ['made' => 0, 'attempts' => 0],
            'tir' => ['made' => 0, 'attempts' => 0],
        ];

        return ['A' => $empty, 'B' => $empty];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyTeamStats(string $team): array
    {
        return [
            'team' => $team,
            'endsWon' => 0,
            'endsOpened' => 0,
            'endsWonWhenOpened' => 0,
            'firstShotSum' => 0,
            'firstShotCount' => 0,
            'capitalizedCount' => 0,
            'capitalizationOpportunities' => 0,
            'capitalizedPointsSum' => 0,
            'defendedCount' => 0,
            'defenseSituations' => 0,
            'defensePointsSum' => 0,
            'reclaimsCount' => 0,
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
}
