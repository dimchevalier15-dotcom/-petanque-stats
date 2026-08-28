<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Response\CoachPlayerListItemResponse;
use App\Dto\Response\CoachPlayerListResponse;
use App\Dto\Response\CoachPlayerShotSummaryResponse;
use App\Dto\Response\PlayerItem;
use App\Entity\Game;
use App\Entity\Player;
use App\Entity\User;
use App\Enum\MatchNature;
use App\Repository\GameBallRepository;
use App\Repository\GameRepository;
use App\Repository\PlayerRepository;
use App\ValueObject\DateRange;
use Doctrine\ORM\EntityManagerInterface;

final class CoachService
{
    public function __construct(
        private PlayerRepository $players,
        private GameRepository $games,
        private GameBallRepository $balls,
        private PlayerItemMapper $playerItemMapper,
        private EntityManagerInterface $em,
    ) {
    }

    public function listPlayersForCoach(User $coach, ?DateRange $dateRange, ?MatchNature $nature = null): CoachPlayerListResponse
    {
        $club = $coach->getCoachForClub();
        if ($club === null) {
            throw new \LogicException('Coach club is required.');
        }

        $clubId = (int) $club->getId();
        $playerList = $this->players->findByClubId($clubId);
        $items = [];

        foreach ($playerList as $player) {
            $items[] = $this->buildListItem($player, $dateRange, $nature);
        }

        return new CoachPlayerListResponse(
            clubId: $clubId,
            clubName: $club->getName(),
            from: $dateRange?->from->format('Y-m-d'),
            to: $dateRange?->to->format('Y-m-d'),
            items: $items,
        );
    }

    /**
     * @return list<PlayerItem>
     */
    public function searchPlayersWithoutClub(string $q): array
    {
        $list = $this->players->searchWithoutClubByQuery(trim($q));
        $out = [];
        foreach ($list as $player) {
            $out[] = $this->playerItemMapper->map($player);
        }

        return $out;
    }

    /**
     * @throws PlayerNotFoundException
     * @throws PlayerAlreadyHasClubException
     */
    public function attachPlayerToCoachClub(User $coach, int $playerId): PlayerItem
    {
        $club = $coach->getCoachForClub();
        if ($club === null) {
            throw new \LogicException('Coach club is required.');
        }

        $player = $this->players->find($playerId);
        if ($player === null) {
            throw new PlayerNotFoundException();
        }

        if ($player->getClub() !== null) {
            throw new PlayerAlreadyHasClubException();
        }

        $player->setClub($club);
        $this->em->flush();

        return $this->playerItemMapper->map($player);
    }

    private function buildListItem(Player $player, ?DateRange $dateRange, ?MatchNature $nature = null): CoachPlayerListItemResponse
    {
        $playerId = (int) $player->getId();
        $games = $this->games->findCompletedGamesForPlayer($playerId, $nature, $dateRange);
        $gameIds = array_map(static fn (Game $game): int => (int) $game->getId(), $games);

        $pointRaw = $gameIds === []
            ? ['count' => 0, 'sum' => 0, 'p2' => 0, 'p1' => 0, 'p0' => 0, 'm1' => 0, 'm2' => 0]
            : ($this->balls->aggregateByPlayerPerShotForGames($playerId, $gameIds)['point'] ?? ['count' => 0, 'sum' => 0, 'p2' => 0, 'p1' => 0, 'p0' => 0, 'm1' => 0, 'm2' => 0]);
        $tirRaw = $gameIds === []
            ? ['count' => 0, 'sum' => 0, 'p2' => 0, 'p1' => 0, 'p0' => 0, 'm1' => 0, 'm2' => 0]
            : ($this->balls->aggregateByPlayerPerShotForGames($playerId, $gameIds)['tir'] ?? ['count' => 0, 'sum' => 0, 'p2' => 0, 'p1' => 0, 'p0' => 0, 'm1' => 0, 'm2' => 0]);

        return new CoachPlayerListItemResponse(
            id: $playerId,
            firstName: $player->getFirstName(),
            lastName: $player->getLastName(),
            nickname: $player->getNickname(),
            point: $this->toShotSummary($pointRaw),
            tir: $this->toShotSummary($tirRaw),
        );
    }

    /**
     * @param array{count:int,sum:int,p2:int,p1:int,p0:int,m1:int,m2:int} $raw
     */
    private function toShotSummary(array $raw): CoachPlayerShotSummaryResponse
    {
        $count = (int) $raw['count'];
        if ($count === 0) {
            return new CoachPlayerShotSummaryResponse(null, null, null);
        }

        $success = (int) $raw['p2'] + (int) $raw['p1'];

        return new CoachPlayerShotSummaryResponse(
            average: round($raw['sum'] / $count, 2),
            successCount: $success,
            totalCount: $count,
        );
    }
}

final class PlayerNotFoundException extends \RuntimeException {}

final class PlayerAlreadyHasClubException extends \RuntimeException {}
