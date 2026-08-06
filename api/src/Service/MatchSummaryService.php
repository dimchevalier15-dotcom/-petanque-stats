<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Response\MatchSummaryPlayerRow;
use App\Dto\Response\MatchSummaryResponse;
use App\Entity\Game;
use App\Repository\GameBallRepository;
use App\Repository\GameEndRepository;
use App\Repository\GameParticipantRepository;
use App\Repository\GameRepository;
use App\Repository\GameTrackedRepository;
use App\Repository\PlayerRepository;

final class MatchSummaryService
{
    public function __construct(
        private GameRepository $games,
        private GameEndRepository $ends,
        private GameParticipantRepository $participants,
        private GameTrackedRepository $tracked,
        private GameBallRepository $balls,
        private PlayerRepository $players,
    ) {
    }

    public function getSummary(int $matchId): ?MatchSummaryResponse
    {
        /** @var Game|null $game */
        $game = $this->games->find($matchId);
        if ($game === null) {
            return null;
        }

        $endsCount = $this->ends->countByGame($game);
        $sum = $this->ends->sumPointsByTeam($game);
        $scoreA = $sum['A'] ?? 0;
        $scoreB = $sum['B'] ?? 0;
        $winner = $scoreA >= $scoreB ? 'A' : 'B';

        // Determine tracked players and their team mapping
        $trackedIds = $this->tracked->findPlayerIdsByGame($game);
        // fallback: if none stored, consider all participants as tracked (defensive)
        if ($trackedIds === []) {
            $trackedIds = $this->participants->findAllPlayerIdsByGame($game);
        }
        $teamMap = $this->participants->mapPlayerTeamByGame($game);

        // Aggregates of notes per player (overall)
        $agg = $this->balls->aggregateByGame($game);
        // Aggregates per shot type
        $shotAgg = $this->balls->aggregateByGamePerShot($game);
        $playerMap = $this->players->findMapByIds($trackedIds);

        $rows = [];
        foreach ($trackedIds as $pid) {
            $p = $playerMap[$pid] ?? null;
            if ($p === null) {
                continue;
            }
            $s = $agg[$pid] ?? ['count' => 0, 'sum' => 0, 'p2' => 0, 'p1' => 0, 'p0' => 0, 'm1' => 0, 'm2' => 0];
            $avg = 0.0;
            if ($s['count'] > 0) {
                $avg = round($s['sum'] / $s['count'], 2);
            }

            $point = null;
            $tir = null;
            if (isset($shotAgg[$pid]['point'])) {
                $ps = $shotAgg[$pid]['point'];
                $pavg = $ps['count'] > 0 ? round($ps['sum'] / $ps['count'], 2) : 0.0;
                $point = new \App\Dto\Response\MatchSummaryShotBreakdown($pavg, (int) $ps['p2'], (int) $ps['p1'], (int) $ps['p0'], (int) $ps['m1'], (int) $ps['m2']);
            }
            if (isset($shotAgg[$pid]['tir'])) {
                $ts = $shotAgg[$pid]['tir'];
                $tavg = $ts['count'] > 0 ? round($ts['sum'] / $ts['count'], 2) : 0.0;
                $tir = new \App\Dto\Response\MatchSummaryShotBreakdown($tavg, (int) $ts['p2'], (int) $ts['p1'], (int) $ts['p0'], (int) $ts['m1'], (int) $ts['m2']);
            }

            $rows[] = new MatchSummaryPlayerRow(
                playerId: $pid,
                firstName: $p->getFirstName(),
                lastName: $p->getLastName(),
                nickname: $p->getNickname(),
                team: $teamMap[$pid] ?? 'A',
                average: $avg,
                p2: (int) $s['p2'],
                p1: (int) $s['p1'],
                p0: (int) $s['p0'],
                m1: (int) $s['m1'],
                m2: (int) $s['m2'],
                point: $point,
                tir: $tir,
            );
        }

        return new MatchSummaryResponse(
            matchId: (int) $game->getId(),
            scoreA: $scoreA,
            scoreB: $scoreB,
            winner: $winner,
            ends: $endsCount,
            players: $rows,
        );
    }
}
