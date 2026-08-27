<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Request\UpdateMatchContextRequest;
use App\Dto\Response\MatchContextResponse;
use App\Entity\Game;
use App\Enum\MatchNature;
use App\Repository\CompetitionRepository;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;

final class MatchContextService
{
    public function __construct(
        private GameRepository $games,
        private CompetitionRepository $competitions,
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
            if ($req->competitionId !== null) {
                $competition = $this->competitions->find($req->competitionId);
                $game->setCompetition($competition);
                $game->setCompetitionName(null);
            } else {
                $game->setCompetition(null);
                $game->setCompetitionName($this->normalizeOptionalString($req->competitionName));
            }
            $game->setCompetitionStage($req->competitionStage);
        } else {
            $game->setCompetition(null);
            $game->setCompetitionName(null);
            $game->setCompetitionStage(null);
        }

        $game->setTerrainType($req->terrainType);
        $this->applyPlayedAt($game, $req->playedAt);

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
            competitionId: $game->getCompetition()?->getId(),
            competitionName: $game->getCompetitionName(),
            competitionStage: $game->getCompetitionStage(),
            terrainType: $game->getTerrainType(),
            playedAt: $game->getPlayedAt()->format('Y-m-d'),
        );
    }

    private function applyPlayedAt(Game $game, ?string $playedAt): void
    {
        if ($playedAt === null || $playedAt === '') {
            return;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $playedAt);
        if ($date === false || $date->format('Y-m-d') !== $playedAt) {
            return;
        }

        $current = $game->getPlayedAt();
        $game->setPlayedAt($current->setDate(
            (int) $date->format('Y'),
            (int) $date->format('n'),
            (int) $date->format('j'),
        ));
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
