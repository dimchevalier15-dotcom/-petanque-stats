<?php

declare(strict_types=1);

namespace App\Service\Pointing;

use App\Dto\Request\CompletePointingSessionRequest;
use App\Dto\Response\PointingSessionStartedResponse;
use App\Dto\Response\PointingSessionSummaryResponse;
use App\Dto\Response\PointingShotSummary;
use App\Dto\Response\PointingWorkshopSummary;
use App\Entity\Player;
use App\Entity\PointingSession;
use App\Entity\PointingShot;
use App\Enum\PointingShotResult;
use App\Enum\PointingWorkshop;
use App\Enum\ShootingDistance;
use App\Repository\PlayerRepository;
use App\Repository\PointingSessionRepository;
use App\Repository\PointingShotRepository;
use App\Service\Auth\CurrentUserService;
use App\Service\PlayerViewContextResolver;
use Doctrine\ORM\EntityManagerInterface;

final class PointingSessionService
{
    public function __construct(
        private CurrentUserService $currentUser,
        private PlayerViewContextResolver $playerViewContext,
        private PlayerRepository $players,
        private PointingSessionRepository $sessions,
        private PointingShotRepository $shots,
        private PointingScoreCalculator $scoring,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @throws NoLinkedPlayerException
     */
    public function start(string $token): PointingSessionStartedResponse
    {
        $player = $this->resolveOwnPlayer($token);

        $session = new PointingSession($player);
        $this->em->persist($session);
        $this->em->flush();

        return $this->toStartedResponse($session);
    }

    /**
     * @throws NoLinkedPlayerException
     */
    public function current(string $token): ?PointingSessionStartedResponse
    {
        $player = $this->resolveOwnPlayer($token);
        $session = $this->sessions->findInProgressForPlayer((int) $player->getId());

        return $session !== null ? $this->toStartedResponse($session) : null;
    }

    public function getSummary(string $token, int $sessionId, ?int $impersonatePlayerId = null): PointingSessionSummaryResponse
    {
        $session = $this->getViewableSession($token, $sessionId, $impersonatePlayerId);

        return $this->toSummaryResponse($session);
    }

    /**
     * @throws NoLinkedPlayerException
     * @throws PointingSessionNotFoundException
     * @throws PointingSessionAccessDeniedException
     * @throws PointingSessionAlreadyFinishedException
     * @throws InvalidPointingSessionStructureException
     */
    public function complete(string $token, int $sessionId, CompletePointingSessionRequest $req): PointingSessionSummaryResponse
    {
        $session = $this->getOwnedSession($token, $sessionId);
        if ($session->isFinished()) {
            throw new PointingSessionAlreadyFinishedException();
        }

        $shotInputs = $this->validateStructure($req);

        $this->em->wrapInTransaction(function () use ($session, $shotInputs): void {
            $total = 0;
            foreach ($shotInputs as [$workshop, $distance, $result]) {
                $score = $this->scoring->pointsFor($workshop, $result);
                $this->em->persist(new PointingShot($session, $workshop, $distance, $result, $score));
                $total += $score;
            }
            $session->markFinished($total);
        });

        return $this->toSummaryResponse($session);
    }

    /**
     * @throws NoLinkedPlayerException
     * @throws PointingSessionNotFoundException
     * @throws PointingSessionAccessDeniedException
     * @throws PointingSessionAlreadyFinishedException
     */
    public function abandon(string $token, int $sessionId): void
    {
        $session = $this->getOwnedSession($token, $sessionId);
        if ($session->isFinished()) {
            throw new PointingSessionAlreadyFinishedException();
        }

        $this->em->remove($session);
        $this->em->flush();
    }

    /**
     * @throws NoLinkedPlayerException
     */
    private function resolveOwnPlayer(string $token): Player
    {
        $user = $this->currentUser->getUserFromToken($token);
        $player = $this->players->findOneByUserId((int) $user->getId());
        if ($player === null) {
            throw new NoLinkedPlayerException();
        }

        return $player;
    }

    /**
     * @throws PointingSessionNotFoundException
     * @throws PointingSessionAccessDeniedException
     */
    private function getViewableSession(string $token, int $sessionId, ?int $impersonatePlayerId = null): PointingSession
    {
        $context = $this->playerViewContext->resolve($token, $impersonatePlayerId);
        if ($context->playerId === null) {
            throw new PointingSessionAccessDeniedException();
        }

        $session = $this->sessions->find($sessionId);
        if ($session === null) {
            throw new PointingSessionNotFoundException();
        }

        if ((int) $session->getPlayer()->getId() !== $context->playerId) {
            throw new PointingSessionAccessDeniedException();
        }

        return $session;
    }

    /**
     * @throws NoLinkedPlayerException
     * @throws PointingSessionNotFoundException
     * @throws PointingSessionAccessDeniedException
     */
    private function getOwnedSession(string $token, int $sessionId): PointingSession
    {
        $player = $this->resolveOwnPlayer($token);

        $session = $this->sessions->find($sessionId);
        if ($session === null) {
            throw new PointingSessionNotFoundException();
        }

        if (!$session->belongsTo($player)) {
            throw new PointingSessionAccessDeniedException();
        }

        return $session;
    }

    /**
     * @return list<array{0:PointingWorkshop,1:ShootingDistance,2:PointingShotResult}>
     * @throws InvalidPointingSessionStructureException
     */
    private function validateStructure(CompletePointingSessionRequest $req): array
    {
        $errors = [];
        $seen = [];
        $parsed = [];

        foreach ($req->shots as $index => $shotInput) {
            $workshop = PointingWorkshop::tryFrom($shotInput->workshop);
            $distance = ShootingDistance::tryFrom($shotInput->distance);
            $result = PointingShotResult::tryFrom($shotInput->result);

            if ($workshop === null || $distance === null || $result === null) {
                $errors["shots[$index]"] = 'Invalid workshop, distance or result.';
                continue;
            }

            $key = $workshop->value.'-'.$distance->value;
            if (isset($seen[$key])) {
                $errors["shots[$index]"] = sprintf('Duplicate shot for workshop %d at %dm.', $workshop->value, $distance->value);
                continue;
            }
            $seen[$key] = true;

            if (!$this->scoring->isResultAllowedForWorkshop($workshop, $result)) {
                $errors["shots[$index]"] = sprintf('Result "%s" is not allowed for workshop %d.', $result->value, $workshop->value);
                continue;
            }

            $parsed[] = [$workshop, $distance, $result];
        }

        if ($errors === []) {
            foreach (PointingWorkshop::all() as $workshop) {
                foreach (ShootingDistance::all() as $distance) {
                    $key = $workshop->value.'-'.$distance->value;
                    if (!isset($seen[$key])) {
                        $errors['shots'] = sprintf('Missing shot for workshop %d at %dm.', $workshop->value, $distance->value);
                    }
                }
            }
        }

        if ($errors !== []) {
            throw InvalidPointingSessionStructureException::withErrors($errors);
        }

        return $parsed;
    }

    private function toStartedResponse(PointingSession $session): PointingSessionStartedResponse
    {
        return new PointingSessionStartedResponse(
            id: (int) $session->getId(),
            createdAt: $session->getCreatedAt()->format(DATE_ATOM),
        );
    }

    private function toSummaryResponse(PointingSession $session): PointingSessionSummaryResponse
    {
        $shots = $this->shots->findBySession($session);

        $byWorkshop = [];
        foreach ($shots as $shot) {
            $byWorkshop[$shot->getWorkshop()->value][] = $shot;
        }

        $workshops = [];
        foreach (PointingWorkshop::all() as $workshop) {
            /** @var list<PointingShot> $shotsForWorkshop */
            $shotsForWorkshop = $byWorkshop[$workshop->value] ?? [];
            $workshopTotal = 0;
            $shotSummaries = [];
            foreach ($shotsForWorkshop as $shot) {
                $workshopTotal += $shot->getScore();
                $shotSummaries[] = new PointingShotSummary(
                    distance: $shot->getDistance()->value,
                    result: $shot->getResult()->value,
                    score: $shot->getScore(),
                );
            }
            $workshops[] = new PointingWorkshopSummary(
                workshop: $workshop->value,
                totalScore: $workshopTotal,
                shots: $shotSummaries,
            );
        }

        return new PointingSessionSummaryResponse(
            id: (int) $session->getId(),
            createdAt: $session->getCreatedAt()->format(DATE_ATOM),
            playedAt: $session->getPlayedAt()->format(DATE_ATOM),
            finishedAt: $session->getFinishedAt()?->format(DATE_ATOM),
            totalScore: $session->getTotalScore(),
            contextNature: $session->getContextNature()?->value,
            title: $session->getTitle(),
            description: $session->getDescription(),
            workshops: $workshops,
        );
    }
}

final class PointingSessionNotFoundException extends \RuntimeException
{
}

final class PointingSessionAccessDeniedException extends \RuntimeException
{
}

final class PointingSessionAlreadyFinishedException extends \RuntimeException
{
}

final class InvalidPointingSessionStructureException extends \RuntimeException
{
    /** @param array<string,string> $errors */
    public function __construct(public array $errors)
    {
        parent::__construct('Invalid pointing session structure.');
    }

    /** @param array<string,string> $errors */
    public static function withErrors(array $errors): self
    {
        return new self($errors);
    }
}

final class NoLinkedPlayerException extends \RuntimeException
{
}
