<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Response\MatchSummaryEndTotal;
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
        private ShotBreakdownFactory $shotBreakdowns,
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
        $shotAgg = $this->balls->aggregateByGamePerShot($game);
        $notesByEnd = $this->balls->sumNotesByPlayerAndEnd($game);
        $endIndexes = [];
        $canceledEndIndexes = [];
        foreach ($this->ends->listIndexMetaByGame($game) as $endMeta) {
            $endIndexes[] = $endMeta['endIndex'];
            if ($endMeta['canceled']) {
                $canceledEndIndexes[] = $endMeta['endIndex'];
            }
        }
        if ($endIndexes === []) {
            $endIndexes = array_keys($notesByEnd);
            sort($endIndexes);
        }
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
                $point = $this->shotBreakdowns->fromAggregate($shotAgg[$pid]['point']);
            }
            if (isset($shotAgg[$pid]['tir'])) {
                $tir = $this->shotBreakdowns->fromAggregate($shotAgg[$pid]['tir']);
            }

            $endTotals = [];
            foreach ($endIndexes as $endIndex) {
                if (!isset($notesByEnd[$endIndex][$pid])) {
                    continue;
                }
                $endTotals[] = new MatchSummaryEndTotal($endIndex, $notesByEnd[$endIndex][$pid]);
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
                endTotals: $endTotals,
            );
        }

        return new MatchSummaryResponse(
            matchId: (int) $game->getId(),
            scoreA: $scoreA,
            scoreB: $scoreB,
            winner: $winner,
            ends: $endsCount,
            type: $game->getType()->value,
            endIndexes: array_values($endIndexes),
            canceledEndIndexes: array_values($canceledEndIndexes),
            players: $rows,
        );
    }
}
