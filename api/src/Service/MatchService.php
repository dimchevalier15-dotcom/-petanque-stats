<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Request\CreateMatchRequest;
use App\Dto\Response\CreateMatchResponse;
use App\Entity\Game;
use App\Entity\GameParticipant;
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

        $game = new Game($req->type, $req->targetScore);
        $this->em->persist($game);
        $pos = 1;
        foreach ($req->teamA as $pid) {
            $this->em->persist(new GameParticipant($game, $map[(int) $pid], 'A', $pos++));
        }
        $pos = 1;
        foreach ($req->teamB as $pid) {
            $this->em->persist(new GameParticipant($game, $map[(int) $pid], 'B', $pos++));
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
