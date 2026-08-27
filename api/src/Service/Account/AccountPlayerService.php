<?php

declare(strict_types=1);

namespace App\Service\Account;

use App\Dto\Request\LinkPlayerRequest;
use App\Dto\Request\UpdatePlayerProfileRequest;
use App\Dto\Response\PlayerItem;
use App\Entity\Player;
use App\Repository\PlayerRepository;
use App\Service\Auth\CurrentUserService;
use App\Service\Auth\InvalidTokenException;
use App\Service\PlayerClubResolver;
use App\Service\PlayerItemMapper;
use Doctrine\ORM\EntityManagerInterface;

final class AccountPlayerService
{
    public function __construct(
        private CurrentUserService $currentUserService,
        private PlayerRepository $players,
        private PlayerLinkService $playerLinkService,
        private PlayerClubResolver $playerClubResolver,
        private PlayerItemMapper $playerItemMapper,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @throws InvalidTokenException
     */
    public function getLinkedPlayer(string $token): ?PlayerItem
    {
        $me = $this->currentUserService->meFromToken($token);
        if ($me->playerId === null) {
            return null;
        }

        $player = $this->players->find($me->playerId);
        if ($player === null) {
            return null;
        }

        return $this->playerItemMapper->map($player);
    }

    /**
     * @return list<PlayerItem>
     *
     * @throws InvalidTokenException
     */
    public function searchUnlinkedPlayers(string $token, ?string $query): array
    {
        $this->currentUserService->meFromToken($token);

        $q = trim((string) ($query ?? ''));
        $list = $this->players->searchUnlinkedByQuery($q, 20);
        $out = [];
        foreach ($list as $player) {
            $out[] = $this->playerItemMapper->map($player);
        }

        return $out;
    }

    /**
     * @throws InvalidTokenException
     * @throws UserAlreadyHasPlayerException
     * @throws PlayerNotFoundException
     * @throws PlayerAlreadyLinkedException
     */
    public function linkPlayer(string $token, LinkPlayerRequest $request): PlayerItem
    {
        $me = $this->currentUserService->meFromToken($token);
        if ($me->playerId !== null) {
            throw new UserAlreadyHasPlayerException();
        }

        $user = $this->currentUserService->getUserFromToken($token);
        $player = $this->playerLinkService->linkToUser($user, $request->playerId);

        return $this->playerItemMapper->map($player);
    }

    /**
     * @throws InvalidTokenException
     * @throws NoLinkedPlayerException
     * @throws PlayerNotFoundException
     */
    public function updateProfile(string $token, UpdatePlayerProfileRequest $request): PlayerItem
    {
        $me = $this->currentUserService->meFromToken($token);
        if ($me->playerId === null) {
            throw new NoLinkedPlayerException();
        }

        $player = $this->players->find($me->playerId);
        if ($player === null) {
            throw new PlayerNotFoundException();
        }

        $firstName = trim($request->firstName);
        $lastName = trim($request->lastName);
        $nickname = $request->nickname !== null && trim($request->nickname) !== ''
            ? trim($request->nickname)
            : $firstName;

        $player->setFirstName($firstName);
        $player->setLastName($lastName);
        $player->setNickname($nickname);
        $player->setClub($this->playerClubResolver->resolveOptional($request->clubId));
        $this->em->flush();

        return $this->playerItemMapper->map($player);
    }
}

final class NoLinkedPlayerException extends \RuntimeException
{
}
