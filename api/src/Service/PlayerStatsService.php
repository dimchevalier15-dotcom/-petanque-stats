<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Response\MatchSummaryShotBreakdown;
use App\Dto\Response\PlayerStatsByNatureResponse;
use App\Dto\Response\PlayerStatsEvolutionPointResponse;
use App\Dto\Response\PlayerStatsResponse;
use App\Dto\Response\PlayerStatsSummaryResponse;
use App\Entity\Game;
use App\Repository\GameBallRepository;
use App\Repository\GameEndRepository;
use App\Repository\GameParticipantRepository;
use App\Repository\GameRepository;
use App\Service\Auth\CurrentUserService;
use App\ValueObject\DateRange;

final class PlayerStatsService
{
    public function __construct(
        private CurrentUserService $currentUser,
        private GameRepository $games,
        private GameEndRepository $ends,
        private GameParticipantRepository $participants,
        private GameBallRepository $balls,
    ) {
    }

    public function statsForToken(string $token, ?DateRange $dateRange = null): PlayerStatsResponse
    {
        $me = $this->currentUser->meFromToken($token);
        $playerId = $me->playerId;

        if ($playerId === null) {
            return $this->emptyResponse('no_player', null, null);
        }

        $displayName = $this->buildDisplayName($me->nickname, $me->firstName, $me->lastName);
        $games = $this->games->findCompletedGamesForPlayer((int) $playerId, $dateRange);

        if ($games === []) {
            if ($dateRange !== null && $this->games->countCompletedGamesForPlayer((int) $playerId) > 0) {
                return $this->emptyResponse('no_data_in_period', (int) $playerId, $displayName);
            }

            return $this->emptyResponse('no_matches', (int) $playerId, $displayName);
        }

        $gameIds = array_map(static fn (Game $game): int => (int) $game->getId(), $games);

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

        $overallRaw = $this->balls->aggregateByPlayerForGames((int) $playerId, $gameIds);
        $shotRaw = $this->balls->aggregateByPlayerPerShotForGames((int) $playerId, $gameIds);
        $perGameRaw = $this->balls->aggregateByPlayerPerGame((int) $playerId, $gameIds);

        $totalBalls = $overallRaw['count'];
        $trackedMatches = count($perGameRaw);

        if ($totalBalls === 0) {
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
            );
        }

        $overall = $this->toBreakdown($overallRaw);
        $point = isset($shotRaw['point']) ? $this->toBreakdown($shotRaw['point']) : null;
        $tir = isset($shotRaw['tir']) ? $this->toBreakdown($shotRaw['tir']) : null;

        $evolution = $this->buildEvolution($games, (int) $playerId, $perGameRaw);
        $byNature = $this->buildByNature($games, (int) $playerId);

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
        );
    }

    /**
     * @param array{count:int,sum:int,p2:int,p1:int,p0:int,m1:int,m2:int} $raw
     */
    private function toBreakdown(array $raw): MatchSummaryShotBreakdown
    {
        $avg = $raw['count'] > 0 ? round($raw['sum'] / $raw['count'], 2) : 0.0;

        return new MatchSummaryShotBreakdown(
            average: $avg,
            p2: (int) $raw['p2'],
            p1: (int) $raw['p1'],
            p0: (int) $raw['p0'],
            m1: (int) $raw['m1'],
            m2: (int) $raw['m2'],
        );
    }

    private function didPlayerWin(Game $game, int $playerId): bool
    {
        $sum = $this->ends->sumPointsByTeam($game);
        $scoreA = $sum['A'] ?? 0;
        $scoreB = $sum['B'] ?? 0;
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
                date: $game->getCreatedAt()->format(DATE_ATOM),
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
    private function buildByNature(array $games, int $playerId): array
    {
        /** @var array<string, list<int>> $gameIdsByNature */
        $gameIdsByNature = [];
        foreach ($games as $game) {
            $nature = $game->getNature();
            if ($nature === null || $nature === '') {
                continue;
            }
            if (!isset($gameIdsByNature[$nature])) {
                $gameIdsByNature[$nature] = [];
            }
            $gameIdsByNature[$nature][] = (int) $game->getId();
        }

        $out = [];
        foreach ($gameIdsByNature as $nature => $gameIds) {
            $raw = $this->balls->aggregateByPlayerForGames($playerId, $gameIds);
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

    private function buildDisplayName(?string $nickname, ?string $firstName, ?string $lastName): ?string
    {
        $full = trim(((string) $firstName).' '.((string) $lastName));
        if ($nickname !== null && $nickname !== '') {
            return $full !== '' ? $nickname.' ('.$full.')' : $nickname;
        }

        return $full !== '' ? $full : null;
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
        );
    }
}
