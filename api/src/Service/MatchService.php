<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Request\CreateMatchRequest;
use App\Dto\Response\CreateMatchResponse;
use App\Entity\Game;
use App\Entity\GameParticipant;
use App\Entity\GameTracked;
use App\Repository\PlayerRepository;
use Doctrine\ORM\EntityManagerInterface;

final class MatchService
{
    public function __construct(
        private PlayerRepository $players,
        private EntityManagerInterface $em,
    ) {}

    /**
     * @return CreateMatchResponse
     * @throws MatchValidationException
     */
    public function create(CreateMatchRequest $req): CreateMatchResponse
    {
        // Dynamic validations to keep exact behavior/messages
        $allowed = ['tete_a_tete' => 1, 'doublette' => 2, 'triplette' => 3];
        if (!isset($allowed[$req->type])) {
            throw MatchValidationException::withErrors(['type' => 'Invalid type.']);
        }
        $expected = $allowed[$req->type];

        if ($req->targetScore <= 0) {
            throw MatchValidationException::withErrors(['targetScore' => 'Must be a positive integer.']);
        }
        if (\count($req->teamA) !== $expected) {
            throw MatchValidationException::withErrors(['teamA' => 'Invalid number of players for team A.']);
        }
        if (\count($req->teamB) !== $expected) {
            throw MatchValidationException::withErrors(['teamB' => 'Invalid number of players for team B.']);
        }

        $allIds = array_map('intval', array_merge($req->teamA, $req->teamB));
        if (\count(array_unique($allIds)) !== \count($allIds)) {
            throw MatchValidationException::withErrors(['players' => 'Duplicate players are not allowed.']);
        }

        $map = $this->players->findMapByIds($allIds);
        foreach ($allIds as $pid) {
            if (!isset($map[$pid])) {
                throw MatchValidationException::withErrors(['players' => 'Unknown player id: '.$pid]);
            }
        }

        // statistics mode validation (also asserted in DTO)
        if (!in_array($req->statisticsMode, ['standard','simple'], true)) {
            throw MatchValidationException::withErrors(['statisticsMode' => 'Invalid statistics mode.']);
        }

        // Normalize tracked players: default to all if empty
        $tracked = $req->trackedPlayers;
        if ($tracked === null || $tracked === []) {
            $tracked = $allIds;
        } else {
            // Ensure all tracked players are part of the match selection
            $tracked = array_values(array_unique(array_map('intval', $tracked)));
            foreach ($tracked as $pid) {
                if (!in_array($pid, $allIds, true)) {
                    throw MatchValidationException::withErrors(['trackedPlayers' => 'Tracked player not in match: '.$pid]);
                }
            }
        }

        $game = new Game($req->type, $req->targetScore, $req->statisticsMode);
        $this->em->persist($game);

        // Build default shot type map from DTO if provided
        $defaults = [];
        foreach ($req->defaultShotTypes as $d) {
            $defaults[(int) $d->playerId] = in_array($d->defaultShotType, ['point','tir'], true) ? $d->defaultShotType : 'point';
        }
        // Helper to compute default by position if not provided
        $computeDefault = function (int $position, string $type) {
            // tete_a_tete: position 1 => point
            // doublette: pos1 => point, pos2 => tir
            // triplette: pos1 => point, pos2 => point (milieu), pos3 => tir
            if ($type === 'doublette' && $position === 2) return 'tir';
            if ($type === 'triplette' && $position === 3) return 'tir';
            return 'point';
        };

        $pos = 1;
        foreach ($req->teamA as $pid) {
            $pid = (int) $pid;
            $def = $defaults[$pid] ?? $computeDefault($pos, $req->type);
            $this->em->persist(new GameParticipant($game, $map[$pid], 'A', $pos++, $def));
        }
        $pos = 1;
        foreach ($req->teamB as $pid) {
            $pid = (int) $pid;
            $def = $defaults[$pid] ?? $computeDefault($pos, $req->type);
            $this->em->persist(new GameParticipant($game, $map[$pid], 'B', $pos++, $def));
        }
        // Persist tracked players
        foreach ($tracked as $pid) {
            $this->em->persist(new GameTracked($game, $map[(int) $pid]));
        }
        $this->em->flush();

        return new CreateMatchResponse((int) $game->getId());
    }
}

final class MatchValidationException extends \RuntimeException
{
    /** @param array<string,string> $errors */
    public function __construct(public array $errors)
    {
        parent::__construct('Invalid match');
    }

    /** @param array<string,string> $errors */
    public static function withErrors(array $errors): self
    {
        return new self($errors);
    }
}
