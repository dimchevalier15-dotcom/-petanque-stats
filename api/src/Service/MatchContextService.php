<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Request\UpdateMatchContextRequest;
use App\Dto\Response\MatchContextResponse;
use App\Entity\Game;
use App\Enum\MatchNature;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;

final class MatchContextService
{
    public function __construct(
        private GameRepository $games,
        private EntityManagerInterface $em,
    ) {
    }

    public function getContext(int $matchId): ?MatchContextResponse
    {
        /** @var Game|null $game */
        $game = $this->games->find($matchId);
        if ($game === null) {
            return null;
        }

        return $this->toResponse($game);
    }

    public function updateContext(int $matchId, UpdateMatchContextRequest $req): ?MatchContextResponse
    {
        /** @var Game|null $game */
        $game = $this->games->find($matchId);
        if ($game === null) {
            return null;
        }

        $game->setComment($this->normalizeOptionalString($req->comment));
        $game->setTeamAName($this->normalizeOptionalString($req->teamAName));
        $game->setTeamBName($this->normalizeOptionalString($req->teamBName));
        $game->setNature($this->resolveNature($req->nature));

        if ($req->nature === MatchNature::COMPETITION->value) {
            $game->setCompetitionName($this->normalizeOptionalString($req->competitionName));
            $game->setCompetitionStage($req->competitionStage);
        } else {
            $game->setCompetitionName(null);
            $game->setCompetitionStage(null);
        }

        $game->setTerrainType($req->terrainType);

        $this->em->flush();

        return $this->toResponse($game);
    }

    private function toResponse(Game $game): MatchContextResponse
    {
        return new MatchContextResponse(
            matchId: (int) $game->getId(),
            comment: $game->getComment(),
            teamAName: $game->getTeamAName(),
            teamBName: $game->getTeamBName(),
            nature: $game->getNature()?->value,
            competitionName: $game->getCompetitionName(),
            competitionStage: $game->getCompetitionStage(),
            terrainType: $game->getTerrainType(),
        );
    }

    private function normalizeOptionalString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function resolveNature(?string $value): ?MatchNature
    {
        if ($value === null || $value === '') {
            return null;
        }

        return MatchNature::from($value);
    }
}
