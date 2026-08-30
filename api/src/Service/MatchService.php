<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Request\CreateMatchRequest;
use App\Dto\Response\CreateMatchResponse;
use App\Entity\Game;
use App\Entity\GameParticipant;
use App\Entity\GameTracked;
use App\Entity\Player;
use App\Entity\User;
use App\Enum\GameType;
use App\Enum\PlayerRole;
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
    public function create(CreateMatchRequest $req, User $createdBy): CreateMatchResponse
    {
        // Dynamic validations to keep exact behavior/messages
        $type = GameType::tryFrom($req->type);
        if ($type === null) {
            throw MatchValidationException::withErrors(['type' => 'Invalid type.']);
        }
        $expected = $type->playersPerTeam();

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

        $game = new Game($type, $req->targetScore, $req->statisticsMode);
        $game->setCreatedBy($createdBy);
        $game->setTeamAName($this->resolveTeamName($req->teamAName, $map[(int) $req->teamA[0]]));
        $game->setTeamBName($this->resolveTeamName($req->teamBName, $map[(int) $req->teamB[0]]));
        $playedAt = $this->resolvePlayedAt($req->playedAt);
        if ($playedAt !== null) {
            $game->setPlayedAt($playedAt);
        }
        $this->em->persist($game);

        $defaults = [];
        foreach ($req->defaultShotTypes as $d) {
            $defaults[(int) $d->playerId] = in_array($d->defaultShotType, ['point','tir'], true) ? $d->defaultShotType : 'point';
        }

        $startingRoles = [];
        foreach ($req->startingRoles as $roleDto) {
            $role = PlayerRole::tryFrom($roleDto->role);
            if ($role !== null) {
                $startingRoles[(int) $roleDto->playerId] = $role;
            }
        }

        $computeDefaultRole = function (int $position) use ($type): PlayerRole {
            if ($type === GameType::DOUBLETTE && $position === 2) {
                return PlayerRole::TIREUR;
            }
            if ($type === GameType::TRIPLETTE) {
                if ($position === 2) {
                    return PlayerRole::MILIEU;
                }
                if ($position === 3) {
                    return PlayerRole::TIREUR;
                }
            }

            return PlayerRole::POINTEUR;
        };

        $pos = 1;
        foreach ($req->teamA as $pid) {
            $pid = (int) $pid;
            $role = $startingRoles[$pid] ?? $computeDefaultRole($pos);
            $def = $defaults[$pid] ?? $role->defaultShotType();
            $this->em->persist(new GameParticipant($game, $map[$pid], 'A', $pos++, $def, $role));
        }
        $pos = 1;
        foreach ($req->teamB as $pid) {
            $pid = (int) $pid;
            $role = $startingRoles[$pid] ?? $computeDefaultRole($pos);
            $def = $defaults[$pid] ?? $role->defaultShotType();
            $this->em->persist(new GameParticipant($game, $map[$pid], 'B', $pos++, $def, $role));
        }
        // Persist tracked players
        foreach ($tracked as $pid) {
            $this->em->persist(new GameTracked($game, $map[(int) $pid]));
        }
        $this->em->flush();

        return new CreateMatchResponse((int) $game->getId());
    }

    private function resolvePlayedAt(?string $value): ?\DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }

    private function resolveTeamName(?string $provided, Player $firstPlayer): string
    {
        $trimmed = $provided !== null ? trim($provided) : '';
        if ($trimmed !== '') {
            return $trimmed;
        }

        return trim($firstPlayer->getLastName());
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
