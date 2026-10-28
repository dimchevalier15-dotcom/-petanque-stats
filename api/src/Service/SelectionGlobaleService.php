<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Request\UpsertSelectionGlobaleRequest;
use App\Dto\Response\SelectionGlobaleCoachItemResponse;
use App\Dto\Response\SelectionGlobaleDetailResponse;
use App\Dto\Response\SelectionGlobaleItemResponse;
use App\Entity\SelectionGlobale;
use App\Entity\SelectionGlobaleCoach;
use App\Entity\SelectionGlobalePlayer;
use App\Entity\User;
use App\Repository\PlayerRepository;
use App\Repository\SelectionGlobaleCoachRepository;
use App\Repository\SelectionGlobalePlayerRepository;
use App\Repository\SelectionGlobaleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final class SelectionGlobaleService
{
    public function __construct(
        private SelectionGlobaleRepository $selections,
        private SelectionGlobaleCoachRepository $selectionCoaches,
        private SelectionGlobalePlayerRepository $selectionPlayers,
        private UserRepository $users,
        private PlayerRepository $players,
        private PlayerItemMapper $playerItemMapper,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @return list<SelectionGlobaleItemResponse>
     */
    public function listAll(): array
    {
        $out = [];
        foreach ($this->selections->findAllOrderedByName() as $selection) {
            $out[] = $this->mapItem($selection);
        }

        return $out;
    }

    /**
     * @return list<SelectionGlobaleItemResponse>
     */
    public function listForCoach(User $user): array
    {
        $out = [];
        foreach ($this->selectionCoaches->findSelectionsForUser($user) as $selection) {
            $out[] = $this->mapItem($selection);
        }

        return $out;
    }

    public function getDetail(int $id): SelectionGlobaleDetailResponse
    {
        $selection = $this->selections->find($id);
        if (!$selection instanceof SelectionGlobale) {
            throw new SelectionGlobaleNotFoundException();
        }

        return $this->mapDetail($selection);
    }

    public function create(UpsertSelectionGlobaleRequest $input): SelectionGlobaleItemResponse
    {
        $selection = new SelectionGlobale(trim($input->name));
        $this->em->persist($selection);
        $this->em->flush();

        return $this->mapItem($selection);
    }

    public function update(SelectionGlobale $selection, UpsertSelectionGlobaleRequest $input): SelectionGlobaleItemResponse
    {
        $selection->setName(trim($input->name));
        $this->em->flush();

        return $this->mapItem($selection);
    }

    public function delete(SelectionGlobale $selection): void
    {
        $this->em->remove($selection);
        $this->em->flush();
    }

    /**
     * @throws UserNotFoundException
     */
    public function addCoach(SelectionGlobale $selection, string $email): SelectionGlobaleDetailResponse
    {
        $user = $this->users->findOneByEmail(trim($email));
        if ($user === null) {
            throw new UserNotFoundException();
        }

        if ($this->selectionCoaches->findOneBySelectionAndUser($selection, $user) === null) {
            $this->em->persist(new SelectionGlobaleCoach($selection, $user));
            $this->em->flush();
        }

        return $this->mapDetail($selection);
    }

    /**
     * @throws UserNotFoundException
     */
    public function removeCoach(SelectionGlobale $selection, string $email): SelectionGlobaleDetailResponse
    {
        $user = $this->users->findOneByEmail(trim($email));
        if ($user === null) {
            throw new UserNotFoundException();
        }

        $link = $this->selectionCoaches->findOneBySelectionAndUser($selection, $user);
        if ($link !== null) {
            $this->em->remove($link);
            $this->em->flush();
        }

        return $this->mapDetail($selection);
    }

    /**
     * @throws PlayerNotFoundException
     * @throws PlayerAlreadyInSelectionGlobaleException
     */
    public function addPlayer(SelectionGlobale $selection, int $playerId): SelectionGlobaleDetailResponse
    {
        $player = $this->players->find($playerId);
        if ($player === null) {
            throw new PlayerNotFoundException();
        }

        if ($this->selectionPlayers->findOneBySelectionAndPlayer($selection, $player) !== null) {
            throw new PlayerAlreadyInSelectionGlobaleException();
        }

        $this->em->persist(new SelectionGlobalePlayer($selection, $player));
        $this->em->flush();

        return $this->mapDetail($selection);
    }

    /**
     * @throws PlayerNotFoundException
     */
    public function removePlayer(SelectionGlobale $selection, int $playerId): SelectionGlobaleDetailResponse
    {
        $player = $this->players->find($playerId);
        if ($player === null) {
            throw new PlayerNotFoundException();
        }

        $link = $this->selectionPlayers->findOneBySelectionAndPlayer($selection, $player);
        if ($link !== null) {
            $this->em->remove($link);
            $this->em->flush();
        }

        return $this->mapDetail($selection);
    }

    private function mapItem(SelectionGlobale $selection): SelectionGlobaleItemResponse
    {
        return new SelectionGlobaleItemResponse(
            id: (int) $selection->getId(),
            name: $selection->getName(),
        );
    }

    private function mapDetail(SelectionGlobale $selection): SelectionGlobaleDetailResponse
    {
        $coaches = [];
        foreach ($this->selectionCoaches->findBySelection($selection) as $link) {
            $user = $link->getUser();
            $coaches[] = new SelectionGlobaleCoachItemResponse(
                userId: (int) $user->getId(),
                email: $user->getEmail(),
            );
        }

        $players = [];
        foreach ($this->selectionPlayers->findBySelection($selection) as $link) {
            $players[] = $this->playerItemMapper->map($link->getPlayer());
        }

        return new SelectionGlobaleDetailResponse(
            id: (int) $selection->getId(),
            name: $selection->getName(),
            coaches: $coaches,
            players: $players,
        );
    }
}

final class SelectionGlobaleNotFoundException extends \RuntimeException {}

final class PlayerAlreadyInSelectionGlobaleException extends \RuntimeException {}
