<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Response\CoachPlayerListItemResponse;
use App\Dto\Response\CoachPlayerListResponse;
use App\Dto\Response\CoachPlayerShootingSummaryResponse;
use App\Dto\Response\CoachPlayerShotSummaryResponse;
use App\Dto\Response\PlayerItem;
use App\Entity\Game;
use App\Entity\Player;
use App\Entity\SelectionGlobale;
use App\Entity\SelectionGlobalePlayer;
use App\Entity\User;
use App\Enum\MatchNature;
use App\Repository\GameBallRepository;
use App\Repository\GameRepository;
use App\Repository\PlayerRepository;
use App\Repository\SelectionGlobalePlayerRepository;
use App\Repository\SelectionGlobaleRepository;
use App\Repository\ShootingSessionRepository;
use App\ValueObject\DateRange;
use Doctrine\ORM\EntityManagerInterface;

final class CoachService
{
    public function __construct(
        private PlayerRepository $players,
        private GameRepository $games,
        private GameBallRepository $balls,
        private PlayerItemMapper $playerItemMapper,
        private SelectionGlobaleRepository $selections,
        private SelectionGlobalePlayerRepository $selectionPlayers,
        private ShootingSessionRepository $shootingSessions,
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
        $items = $this->buildListItems($playerList, $dateRange, $nature);

        return new CoachPlayerListResponse(
            clubId: $clubId,
            clubName: $club->getName(),
            selectionGlobaleId: null,
            selectionGlobaleName: null,
            from: $dateRange?->from->format('Y-m-d'),
            to: $dateRange?->to->format('Y-m-d'),
            items: $items,
        );
    }

    public function listPlayersForSelectionGlobaleCoach(
        User $coach,
        int $selectionGlobaleId,
        ?DateRange $dateRange,
        ?MatchNature $nature = null,
    ): CoachPlayerListResponse {
        $selection = $this->selections->find($selectionGlobaleId);
        if (!$selection instanceof SelectionGlobale) {
            throw new SelectionGlobaleNotFoundException();
        }

        $playerList = $this->selectionPlayers->findPlayersBySelectionId($selectionGlobaleId);
        $items = $this->buildListItems($playerList, $dateRange, $nature);

        return new CoachPlayerListResponse(
            clubId: null,
            clubName: null,
            selectionGlobaleId: $selectionGlobaleId,
            selectionGlobaleName: $selection->getName(),
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
     * @return list<PlayerItem>
     */
    public function searchPlayersForSelectionAttach(string $q, int $selectionGlobaleId): array
    {
        $list = $this->players->searchByQuery(trim($q));
        $out = [];
        foreach ($list as $player) {
            $playerId = (int) $player->getId();
            if ($this->selectionPlayers->isPlayerInSelection($selectionGlobaleId, $playerId)) {
                continue;
            }
            $out[] = $this->playerItemMapper->map($player);
        }

        return $out;
    }

    /**
     * @throws PlayerNotFoundException
     * @throws PlayerAlreadyInSelectionGlobaleException
     */
    public function attachPlayerToSelectionGlobale(User $coach, int $selectionGlobaleId, int $playerId): PlayerItem
    {
        $selection = $this->selections->find($selectionGlobaleId);
        if (!$selection instanceof SelectionGlobale) {
            throw new SelectionGlobaleNotFoundException();
        }

        $player = $this->players->find($playerId);
        if ($player === null) {
            throw new PlayerNotFoundException();
        }

        if ($this->selectionPlayers->findOneBySelectionAndPlayer($selection, $player) !== null) {
            throw new PlayerAlreadyInSelectionGlobaleException();
        }

        $this->em->persist(new SelectionGlobalePlayer($selection, $player));
        $this->em->flush();

        return $this->playerItemMapper->map($player);
    }

    /**
     * @throws SelectionGlobaleNotFoundException
     */
    public function createPlayerForSelectionGlobale(
        User $coach,
        int $selectionGlobaleId,
        string $firstName,
        string $lastName,
        ?string $nickname,
    ): PlayerItem {
        $selection = $this->selections->find($selectionGlobaleId);
        if (!$selection instanceof SelectionGlobale) {
            throw new SelectionGlobaleNotFoundException();
        }

        $player = new Player($firstName, $lastName, $nickname ?? '');
        $this->em->persist($player);
        $this->em->persist(new SelectionGlobalePlayer($selection, $player));
        $this->em->flush();

        return $this->playerItemMapper->map($player);
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

    /**
     * @param list<Player> $playerList
     *
     * @return list<CoachPlayerListItemResponse>
     */
    private function buildListItems(array $playerList, ?DateRange $dateRange, ?MatchNature $nature): array
    {
        $playerIds = array_map(static fn (Player $player): int => (int) $player->getId(), $playerList);
        $shootingMap = $this->shootingSessions->summarizeCompletedForPlayers($playerIds, $dateRange);

        $items = [];
        foreach ($playerList as $player) {
            $playerId = (int) $player->getId();
            $items[] = $this->buildListItem(
                $player,
                $dateRange,
                $nature,
                $shootingMap[$playerId] ?? null,
            );
        }

        return $items;
    }

    /**
     * @param array{sessionsCount:int,averageSessionScore:float|null,bestSessionScore:int|null}|null $shootingRaw
     */
    private function buildListItem(
        Player $player,
        ?DateRange $dateRange,
        ?MatchNature $nature,
        ?array $shootingRaw = null,
    ): CoachPlayerListItemResponse {
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
            shooting: $this->toShootingSummary($shootingRaw),
        );
    }

    /**
     * @param array{sessionsCount:int,averageSessionScore:float|null,bestSessionScore:int|null}|null $raw
     */
    private function toShootingSummary(?array $raw): CoachPlayerShootingSummaryResponse
    {
        if ($raw === null) {
            return new CoachPlayerShootingSummaryResponse(0, null, null);
        }

        return new CoachPlayerShootingSummaryResponse(
            sessionsCount: $raw['sessionsCount'],
            averageSessionScore: $raw['averageSessionScore'],
            bestSessionScore: $raw['bestSessionScore'],
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
