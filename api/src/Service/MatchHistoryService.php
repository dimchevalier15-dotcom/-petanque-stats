<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Response\MatchHistoryItemResponse;
use App\Dto\Response\MatchHistoryResponse;
use App\Entity\Game;
use App\Repository\GameEndRepository;
use App\Repository\GameRepository;
use App\Repository\PlayerRepository;
use App\Repository\GameParticipantRepository;
use App\Service\Auth\CurrentUserService;

final class MatchHistoryService
{
    public function __construct(
        private CurrentUserService $currentUser,
        private GameRepository $games,
        private GameEndRepository $ends,
        private PlayerRepository $players,
        private GameParticipantRepository $participants,
    ) {
    }

    public function historyForToken(string $token, int $page = 1, int $pageSize = 20): MatchHistoryResponse
    {
        $me = $this->currentUser->meFromToken($token);
        $playerId = $me->playerId;
        if ($playerId === null) {
            return new MatchHistoryResponse(page: $page, pageSize: $pageSize, total: 0, items: []);
        }

        // Fetch paginated games for this player
        [$total, $games] = $this->games->findHistoryForPlayer((int) $playerId, $page, $pageSize);

        $items = [];
        foreach ($games as $g) {
            if (!$g instanceof Game) continue;
            $sum = $this->ends->sumPointsByTeam($g);
            $scoreA = $sum['A'] ?? 0;
            $scoreB = $sum['B'] ?? 0;
            $winner = $scoreA >= $scoreB ? 'A' : 'B';
            // Determine if current player's team won
            $teamMap = $this->participants->mapPlayerTeamByGame($g);
            $team = $teamMap[$playerId] ?? 'A';
            $victory = ($winner === $team);

            $items[] = new MatchHistoryItemResponse(
                id: (int) $g->getId(),
                date: $g->getCreatedAt()->format(DATE_ATOM),
                type: $g->getType()->value,
                scoreA: $scoreA,
                scoreB: $scoreB,
                winner: $winner,
                victory: $victory,
            );
        }

        return new MatchHistoryResponse(page: $page, pageSize: $pageSize, total: $total, items: $items);
    }
}
