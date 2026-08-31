<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Response\MatchPendingValidationItemResponse;
use App\Dto\Response\MatchPendingValidationResponse;
use App\Dto\Response\PendingValidationCountResponse;
use App\Entity\Game;
use App\Entity\GameParticipant;
use App\Repository\GameEndRepository;
use App\Repository\GameParticipantRepository;
use App\Repository\PlayerRepository;
use App\Service\Auth\InvalidTokenException;
use Doctrine\ORM\EntityManagerInterface;

final class MatchValidationService
{
    public function __construct(
        private PlayerViewContextResolver $playerViewContext,
        private GameParticipantRepository $participants,
        private GameEndRepository $ends,
        private PlayerRepository $players,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @throws InvalidTokenException
     */
    public function pendingForToken(string $token, ?int $impersonatePlayerId = null): MatchPendingValidationResponse
    {
        $context = $this->playerViewContext->resolve($token, $impersonatePlayerId);
        if ($context->playerId === null) {
            return new MatchPendingValidationResponse(total: 0, items: []);
        }

        $participations = $this->participants->findPendingValidationForPlayer($context->playerId);
        $items = [];
        foreach ($participations as $participation) {
            $items[] = $this->toItemResponse($participation);
        }

        return new MatchPendingValidationResponse(total: \count($items), items: $items);
    }

    /**
     * @throws InvalidTokenException
     */
    public function countPendingForToken(string $token, ?int $impersonatePlayerId = null): PendingValidationCountResponse
    {
        $context = $this->playerViewContext->resolve($token, $impersonatePlayerId);
        if ($context->playerId === null) {
            return new PendingValidationCountResponse(0);
        }

        return new PendingValidationCountResponse(
            $this->participants->countPendingValidationForPlayer($context->playerId),
        );
    }

    /**
     * @throws InvalidTokenException
     * @throws MatchValidationOwnershipException
     */
    public function updateValidation(
        string $token,
        int $matchPlayerId,
        bool $validated,
        ?int $impersonatePlayerId = null,
    ): void {
        $context = $this->playerViewContext->resolve($token, $impersonatePlayerId);
        if ($context->playerId === null) {
            throw new MatchValidationOwnershipException();
        }

        $participation = $this->participants->find($matchPlayerId);
        if ($participation === null) {
            throw new MatchValidationOwnershipException();
        }

        if ((int) $participation->getPlayer()->getId() !== $context->playerId) {
            throw new MatchValidationOwnershipException();
        }

        $participation->setHasValidatedMatch($validated);
        $this->em->flush();
    }

    private function toItemResponse(GameParticipant $participation): MatchPendingValidationItemResponse
    {
        $game = $participation->getGame();
        $sum = $this->ends->sumPointsByTeam($game);
        $scoreA = $game->getOpeningScoreA() + ($sum['A'] ?? 0);
        $scoreB = $game->getOpeningScoreB() + ($sum['B'] ?? 0);

        return new MatchPendingValidationItemResponse(
            matchPlayerId: (int) $participation->getId(),
            matchId: (int) $game->getId(),
            date: $game->getPlayedAt()->format(DATE_ATOM),
            type: $game->getType()->value,
            scoreA: $scoreA,
            scoreB: $scoreB,
            teamALabel: $this->buildTeamLabel($game, 'A'),
            teamBLabel: $this->buildTeamLabel($game, 'B'),
            nature: $game->getNature()?->value,
            competitionLabel: $this->resolveCompetitionLabel($game),
            competitionStage: $game->getCompetitionStage(),
        );
    }

    private function buildTeamLabel(Game $game, string $team): string
    {
        $customName = $team === 'A' ? $game->getTeamAName() : $game->getTeamBName();
        $participants = $this->participants->listParticipantsByGame($game);
        $names = [];
        foreach ($participants as $row) {
            if ($row['team'] !== $team) {
                continue;
            }
            $names[] = $this->formatPlayerName($row['firstName'], $row['lastName'], $row['nickname']);
        }

        if ($names !== []) {
            return implode(' / ', $names);
        }

        return $customName ?? $team;
    }

    private function formatPlayerName(string $firstName, string $lastName, string $nickname): string
    {
        $nickname = trim($nickname);
        if ($nickname !== '') {
            return $nickname;
        }

        return trim($firstName.' '.$lastName);
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

final class MatchValidationOwnershipException extends \RuntimeException {}
