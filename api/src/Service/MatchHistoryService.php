<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Response\MatchHistoryItemResponse;
use App\Dto\Response\MatchHistoryResponse;
use App\Entity\Game;
use App\Repository\GameEndRepository;
use App\Repository\GameRepository;
use App\Repository\GameParticipantRepository;
use App\Repository\PlayerRepository;
use App\Service\Auth\InvalidTokenException;
use App\ValueObject\MatchHistoryFilters;

final class MatchHistoryService
{
    public function __construct(
        private PlayerViewContextResolver $playerViewContext,
        private PlayerRepository $players,
        private GameRepository $games,
        private GameEndRepository $ends,
        private GameParticipantRepository $participants,
    ) {
    }

    /**
     * @throws InvalidTokenException
     */
    public function historyForToken(
        string $token,
        int $page = 1,
        int $pageSize = 20,
        ?int $impersonatePlayerId = null,
        MatchHistoryFilters $filters = new MatchHistoryFilters(),
    ): MatchHistoryResponse {
        $context = $this->playerViewContext->resolve($token, $impersonatePlayerId);

        return $this->historyForPlayerContext(
            $context->historyUserId,
            $context->playerId,
            $page,
            $pageSize,
            $filters,
        );
    }

    public function historyForPlayerId(
        int $playerId,
        int $page = 1,
        int $pageSize = 20,
        MatchHistoryFilters $filters = new MatchHistoryFilters(),
    ): MatchHistoryResponse {
        $player = $this->players->find($playerId);
        if ($player === null) {
            return new MatchHistoryResponse(page: $page, pageSize: $pageSize, total: 0, items: []);
        }

        return $this->historyForPlayerContext(
            $player->getUser()?->getId(),
            $playerId,
            $page,
            $pageSize,
            $filters,
        );
    }

    private function historyForPlayerContext(
        ?int $historyUserId,
        ?int $playerId,
        int $page,
        int $pageSize,
        MatchHistoryFilters $filters,
    ): MatchHistoryResponse {
        [$total, $games] = $this->games->findHistoryForAccount(
            $historyUserId,
            $playerId,
            $page,
            $pageSize,
            $filters,
        );

        $gameIds = array_values(array_filter(array_map(
            static fn ($g) => $g instanceof Game ? (int) $g->getId() : null,
            $games,
        )));
        $validationMap = $playerId !== null
            ? $this->participants->mapValidationStatusByGameIds($playerId, $gameIds)
            : [];

        $items = [];
        foreach ($games as $g) {
            if (!$g instanceof Game) {
                continue;
            }
            $gameId = (int) $g->getId();
            $refused = ($validationMap[$gameId] ?? null) === false;
            $sum = $this->ends->sumPointsByTeam($g);
            $scoreA = $g->getOpeningScoreA() + ($sum['A'] ?? 0);
            $scoreB = $g->getOpeningScoreB() + ($sum['B'] ?? 0);
            $winner = $scoreA >= $scoreB ? 'A' : 'B';
            $victory = $refused ? null : $this->resolveVictory($g, $playerId, $winner);

            $items[] = new MatchHistoryItemResponse(
                id: $gameId,
                date: $g->getPlayedAt()->format(DATE_ATOM),
                type: $g->getType()->value,
                scoreA: $scoreA,
                scoreB: $scoreB,
                winner: $winner,
                victory: $victory,
                nature: $g->getNature()?->value,
                competitionLabel: $this->resolveCompetitionLabel($g),
                competitionStage: $g->getCompetitionStage(),
                refused: $refused,
            );
        }

        return new MatchHistoryResponse(page: $page, pageSize: $pageSize, total: $total, items: $items);
    }

    private function resolveVictory(Game $game, ?int $playerId, string $winner): ?bool
    {
        if ($playerId === null) {
            return null;
        }

        $teamMap = $this->participants->mapPlayerTeamByGame($game);
        if (!isset($teamMap[$playerId])) {
            return null;
        }

        return $winner === $teamMap[$playerId];
    }

    private function resolveCompetitionLabel(Game $game): ?string
    {
        $competition = $game->getCompetition();
        if ($competition !== null) {
            return sprintf('%s - %s', $competition->getName(), $competition->getEventDate()->format('Y'));
        }

        $name = $game->getCompetitionName();
        if ($name === null || trim($name) === '') {
            return null;
        }

        return trim($name);
    }
}
