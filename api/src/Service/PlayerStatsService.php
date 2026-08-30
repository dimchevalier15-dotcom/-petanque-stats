<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Response\MatchSummaryShotBreakdown;
use App\Dto\Response\PlayerStatsByDistanceResponse;
use App\Dto\Response\PlayerStatsByFormatResponse;
use App\Dto\Response\PlayerStatsByNatureResponse;
use App\Dto\Response\PlayerStatsEvolutionPointResponse;
use App\Dto\Response\PlayerStatsResponse;
use App\Dto\Response\PlayerStatsSummaryResponse;
use App\Entity\Game;
use App\Enum\DistanceBucket;
use App\Enum\GameType;
use App\Enum\MatchNature;
use App\Repository\GameBallRepository;
use App\Repository\GameEndRepository;
use App\Repository\GameParticipantRepository;
use App\Repository\GameRepository;
use App\ValueObject\DateRange;

final class PlayerStatsService
{
    public function __construct(
        private PlayerViewContextResolver $playerViewContext,
        private GameRepository $games,
        private GameEndRepository $ends,
        private GameParticipantRepository $participants,
        private GameBallRepository $balls,
        private ShotBreakdownFactory $shotBreakdowns,
    ) {
    }

    public function statsForToken(
        string $token,
        ?MatchNature $nature = null,
        ?DateRange $dateRange = null,
        ?GameType $type = null,
        ?DistanceBucket $distanceBucket = null,
        ?int $competitionId = null,
        ?int $impersonatePlayerId = null,
    ): PlayerStatsResponse {
        $context = $this->playerViewContext->resolve($token, $impersonatePlayerId);
        $playerId = $context->playerId;

        if ($playerId === null) {
            return $this->emptyResponse('no_player', null, null);
        }

        return $this->statsForPlayerId(
            $playerId,
            $context->displayName,
            $nature,
            $dateRange,
            $type,
            $distanceBucket,
            $competitionId,
        );
    }

    public function statsForPlayerId(
        int $playerId,
        ?string $displayName,
        ?MatchNature $nature = null,
        ?DateRange $dateRange = null,
        ?GameType $type = null,
        ?DistanceBucket $distanceBucket = null,
        ?int $competitionId = null,
    ): PlayerStatsResponse {
        $games = $this->games->findCompletedGamesForPlayer((int) $playerId, $nature, $dateRange, $type, $competitionId);

        if ($games === []) {
            if ($this->hasDataOutsideFilters((int) $playerId, $nature, $dateRange, $type, $competitionId)) {
                return $this->emptyResponse('no_data_in_period', (int) $playerId, $displayName);
            }

            return $this->emptyResponse('no_matches', (int) $playerId, $displayName);
        }

        $gameIds = array_map(static fn (Game $game): int => (int) $game->getId(), $games);

        $overallRaw = $this->balls->aggregateByPlayerForGames((int) $playerId, $gameIds, $distanceBucket);
        $shotRaw = $this->balls->aggregateByPlayerPerShotForGames((int) $playerId, $gameIds, $distanceBucket);
        $perGameRaw = $this->balls->aggregateByPlayerPerGame((int) $playerId, $gameIds, $distanceBucket);

        $totalBalls = $overallRaw['count'];
        $trackedMatches = count(array_filter($perGameRaw, static fn (array $raw): bool => $raw['count'] > 0));

        if ($totalBalls === 0) {
            if ($distanceBucket !== null) {
                $ballsWithoutDistanceFilter = $this->balls->aggregateByPlayerForGames((int) $playerId, $gameIds);
                if ($ballsWithoutDistanceFilter['count'] > 0) {
                    return $this->emptyResponse('no_data_in_period', (int) $playerId, $displayName);
                }
            }

            $victories = 0;
            $defeats = 0;
            foreach ($games as $game) {
                if ($this->didPlayerWin($game, (int) $playerId)) {
                    $victories++;
                } else {
                    $defeats++;
                }
            }
            $matchesPlayed = count($games);
            $winRate = $matchesPlayed > 0 ? round(($victories / $matchesPlayed) * 100, 1) : null;

            return new PlayerStatsResponse(
                status: 'no_tracked_data',
                playerId: (int) $playerId,
                displayName: $displayName,
                summary: new PlayerStatsSummaryResponse(
                    matchesPlayed: $matchesPlayed,
                    victories: $victories,
                    defeats: $defeats,
                    winRate: $winRate,
                    trackedMatches: 0,
                    totalBalls: 0,
                ),
                overall: null,
                point: null,
                tir: null,
                evolution: [],
                byNature: [],
                byFormat: [],
                byDistance: [],
            );
        }

        $summaryGames = $this->gamesForSummary($games, $perGameRaw, $distanceBucket);
        $victories = 0;
        $defeats = 0;
        foreach ($summaryGames as $game) {
            if ($this->didPlayerWin($game, (int) $playerId)) {
                $victories++;
            } else {
                $defeats++;
            }
        }

        $matchesPlayed = count($summaryGames);
        $winRate = $matchesPlayed > 0 ? round(($victories / $matchesPlayed) * 100, 1) : null;

        $overall = $this->toBreakdown($overallRaw);
        $point = isset($shotRaw['point']) ? $this->toBreakdown($shotRaw['point']) : null;
        $tir = isset($shotRaw['tir']) ? $this->toBreakdown($shotRaw['tir']) : null;

        $evolution = $this->buildEvolution($summaryGames, (int) $playerId, $perGameRaw);
        $byNature = $this->buildByNature($games, (int) $playerId, $distanceBucket, $perGameRaw);
        $byFormat = $this->buildByFormat($games, (int) $playerId, $distanceBucket, $perGameRaw);
        $byDistance = $distanceBucket === null
            ? $this->buildByDistance((int) $playerId, $gameIds)
            : [];

        return new PlayerStatsResponse(
            status: 'ok',
            playerId: (int) $playerId,
            displayName: $displayName,
            summary: new PlayerStatsSummaryResponse(
                matchesPlayed: $matchesPlayed,
                victories: $victories,
                defeats: $defeats,
                winRate: $winRate,
                trackedMatches: $trackedMatches,
                totalBalls: $totalBalls,
            ),
            overall: $overall,
            point: $point,
            tir: $tir,
            evolution: $evolution,
            byNature: $byNature,
            byFormat: $byFormat,
            byDistance: $byDistance,
        );
    }

    /**
     * @param array{count:int,sum:int,p2:int,p1:int,p0:int,m1:int,m2:int} $raw
     */
    private function toBreakdown(array $raw): MatchSummaryShotBreakdown
    {
        return $this->shotBreakdowns->fromAggregate($raw);
    }

    private function didPlayerWin(Game $game, int $playerId): bool
    {
        $sum = $this->ends->sumPointsByTeam($game);
        $scoreA = $game->getOpeningScoreA() + ($sum['A'] ?? 0);
        $scoreB = $game->getOpeningScoreB() + ($sum['B'] ?? 0);
        $winner = $scoreA >= $scoreB ? 'A' : 'B';
        $teamMap = $this->participants->mapPlayerTeamByGame($game);
        $team = $teamMap[$playerId] ?? 'A';

        return $winner === $team;
    }

    /**
     * @param list<Game> $games
     * @param array<int, array{count:int,sum:int,p2:int,p1:int,p0:int,m1:int,m2:int}> $perGameRaw
     *
     * @return list<PlayerStatsEvolutionPointResponse>
     */
    private function buildEvolution(array $games, int $playerId, array $perGameRaw): array
    {
        $points = [];
        foreach ($games as $game) {
            $gameId = (int) $game->getId();
            if (!isset($perGameRaw[$gameId]) || $perGameRaw[$gameId]['count'] === 0) {
                continue;
            }
            $raw = $perGameRaw[$gameId];
            $avg = round($raw['sum'] / $raw['count'], 2);
            $points[] = new PlayerStatsEvolutionPointResponse(
                matchId: $gameId,
                date: $game->getPlayedAt()->format(DATE_ATOM),
                average: $avg,
                victory: $this->didPlayerWin($game, $playerId),
            );
        }

        return $points;
    }

    /**
     * @param list<Game> $games
     *
     * @return list<PlayerStatsByNatureResponse>
     */
    private function buildByNature(array $games, int $playerId, ?DistanceBucket $distanceBucket = null, array $perGameRaw = []): array
    {
        /** @var array<string, list<int>> $gameIdsByNature */
        $gameIdsByNature = [];
        foreach ($games as $game) {
            $natureValue = $game->getNature()?->value;
            if ($natureValue === null || $natureValue === '') {
                continue;
            }
            if (!isset($gameIdsByNature[$natureValue])) {
                $gameIdsByNature[$natureValue] = [];
            }
            $gameIdsByNature[$natureValue][] = (int) $game->getId();
        }

        $out = [];
        foreach ($gameIdsByNature as $nature => $gameIds) {
            if ($distanceBucket !== null) {
                $gameIds = array_values(array_filter(
                    $gameIds,
                    static fn (int $gameId): bool => ($perGameRaw[$gameId]['count'] ?? 0) > 0,
                ));
                if ($gameIds === []) {
                    continue;
                }
            }

            $raw = $this->balls->aggregateByPlayerForGames($playerId, $gameIds, $distanceBucket);
            if ($raw['count'] === 0) {
                continue;
            }
            $out[] = new PlayerStatsByNatureResponse(
                nature: $nature,
                matchCount: count($gameIds),
                ballCount: (int) $raw['count'],
                average: round($raw['sum'] / $raw['count'], 2),
            );
        }

        usort($out, static fn (PlayerStatsByNatureResponse $a, PlayerStatsByNatureResponse $b): int => $b->ballCount <=> $a->ballCount);

        return $out;
    }

    /**
     * @param list<Game> $games
     *
     * @return list<PlayerStatsByFormatResponse>
     */
    private function buildByFormat(array $games, int $playerId, ?DistanceBucket $distanceBucket = null, array $perGameRaw = []): array
    {
        /** @var array<string, list<Game>> $gamesByType */
        $gamesByType = [];
        foreach ($games as $game) {
            $typeValue = $game->getType()->value;
            if (!isset($gamesByType[$typeValue])) {
                $gamesByType[$typeValue] = [];
            }
            $gamesByType[$typeValue][] = $game;
        }

        $out = [];
        foreach ($gamesByType as $typeValue => $typeGames) {
            if ($distanceBucket !== null) {
                $typeGames = array_values(array_filter(
                    $typeGames,
                    static fn (Game $game): bool => ($perGameRaw[(int) $game->getId()]['count'] ?? 0) > 0,
                ));
                if ($typeGames === []) {
                    continue;
                }
            }

            $gameIds = array_map(static fn (Game $game): int => (int) $game->getId(), $typeGames);
            $raw = $this->balls->aggregateByPlayerForGames($playerId, $gameIds, $distanceBucket);
            if ($raw['count'] === 0) {
                continue;
            }

            $victories = 0;
            foreach ($typeGames as $game) {
                if ($this->didPlayerWin($game, $playerId)) {
                    $victories++;
                }
            }

            $out[] = new PlayerStatsByFormatResponse(
                type: $typeValue,
                matchCount: count($typeGames),
                victories: $victories,
                ballCount: (int) $raw['count'],
                average: round($raw['sum'] / $raw['count'], 2),
            );
        }

        usort($out, static fn (PlayerStatsByFormatResponse $a, PlayerStatsByFormatResponse $b): int => $b->matchCount <=> $a->matchCount);

        return $out;
    }

    /**
     * @param list<int> $gameIds
     *
     * @return list<PlayerStatsByDistanceResponse>
     */
    private function buildByDistance(int $playerId, array $gameIds): array
    {
        $rawByBucket = $this->balls->aggregateByPlayerPerDistanceBucketForGames($playerId, $gameIds);
        $out = [];

        foreach ($rawByBucket as $bucket => $raw) {
            if ($raw['count'] === 0) {
                continue;
            }
            $out[] = new PlayerStatsByDistanceResponse(
                bucket: $bucket,
                ballCount: (int) $raw['count'],
                average: round($raw['sum'] / $raw['count'], 2),
                p2: (int) $raw['p2'],
                p1: (int) $raw['p1'],
                p0: (int) $raw['p0'],
                m1: (int) $raw['m1'],
                m2: (int) $raw['m2'],
            );
        }

        usort($out, static function (PlayerStatsByDistanceResponse $a, PlayerStatsByDistanceResponse $b): int {
            $orderA = DistanceBucket::tryFrom($a->bucket)?->sortOrder() ?? 99;
            $orderB = DistanceBucket::tryFrom($b->bucket)?->sortOrder() ?? 99;

            return $orderA <=> $orderB;
        });

        return $out;
    }

    /**
     * @param list<Game> $games
     * @param array<int, array{count:int,sum:int,p2:int,p1:int,p0:int,m1:int,m2:int}> $perGameRaw
     *
     * @return list<Game>
     */
    private function gamesForSummary(array $games, array $perGameRaw, ?DistanceBucket $distanceBucket): array
    {
        if ($distanceBucket === null) {
            return $games;
        }

        return array_values(array_filter(
            $games,
            static function (Game $game) use ($perGameRaw): bool {
                $gameId = (int) $game->getId();

                return isset($perGameRaw[$gameId]) && $perGameRaw[$gameId]['count'] > 0;
            },
        ));
    }

    private function hasDataOutsideFilters(
        int $playerId,
        ?MatchNature $nature,
        ?DateRange $dateRange,
        ?GameType $type,
        ?int $competitionId = null,
    ): bool {
        if ($dateRange !== null && $this->games->countCompletedGamesForPlayer($playerId, $nature, null, $type, $competitionId) > 0) {
            return true;
        }

        if ($nature !== null && $this->games->countCompletedGamesForPlayer($playerId, null, $dateRange, $type, $competitionId) > 0) {
            return true;
        }

        if ($type !== null && $this->games->countCompletedGamesForPlayer($playerId, $nature, $dateRange, null, $competitionId) > 0) {
            return true;
        }

        if ($competitionId !== null && $this->games->countCompletedGamesForPlayer($playerId, $nature, $dateRange, $type, null) > 0) {
            return true;
        }

        return false;
    }

    private function emptyResponse(string $status, ?int $playerId, ?string $displayName): PlayerStatsResponse
    {
        return new PlayerStatsResponse(
            status: $status,
            playerId: $playerId,
            displayName: $displayName,
            summary: new PlayerStatsSummaryResponse(
                matchesPlayed: 0,
                victories: 0,
                defeats: 0,
                winRate: null,
                trackedMatches: 0,
                totalBalls: 0,
            ),
            overall: null,
            point: null,
            tir: null,
            evolution: [],
            byNature: [],
            byFormat: [],
            byDistance: [],
        );
    }
}
