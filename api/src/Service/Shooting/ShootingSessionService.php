<?php

declare(strict_types=1);

namespace App\Service\Shooting;

use App\Dto\Request\CompleteShootingSessionRequest;
use App\Dto\Request\UpdateShootingSessionContextRequest;
use App\Dto\Response\ShootingSessionHistoryItemResponse;
use App\Dto\Response\ShootingSessionHistoryResponse;
use App\Dto\Response\ShootingSessionStartedResponse;
use App\Dto\Response\ShootingSessionSummaryResponse;
use App\Dto\Response\ShootingShotSummary;
use App\Dto\Response\ShootingWorkshopSummary;
use App\Entity\Player;
use App\Entity\ShootingSession;
use App\Entity\ShootingShot;
use App\Enum\ShootingContextNature;
use App\Enum\ShootingDistance;
use App\Enum\ShootingShotResult;
use App\Enum\ShootingWorkshop;
use App\Repository\PlayerRepository;
use App\Repository\ShootingSessionRepository;
use App\Repository\ShootingShotRepository;
use App\Service\Auth\CurrentUserService;
use Doctrine\ORM\EntityManagerInterface;

final class ShootingSessionService
{
    public function __construct(
        private CurrentUserService $currentUser,
        private PlayerRepository $players,
        private ShootingSessionRepository $sessions,
        private ShootingShotRepository $shots,
        private ShootingScoreCalculator $scoring,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @throws NoLinkedPlayerException
     */
    public function start(string $token): ShootingSessionStartedResponse
    {
        $player = $this->resolveOwnPlayer($token);

        $session = new ShootingSession($player);
        $this->em->persist($session);
        $this->em->flush();

        return $this->toStartedResponse($session);
    }

    /**
     * The in-progress session for the current player, if any.
     *
     * @throws NoLinkedPlayerException
     */
    public function current(string $token): ?ShootingSessionStartedResponse
    {
        $player = $this->resolveOwnPlayer($token);
        $session = $this->sessions->findInProgressForPlayer((int) $player->getId());

        return $session !== null ? $this->toStartedResponse($session) : null;
    }

    /**
     * @throws NoLinkedPlayerException
     * @throws ShootingSessionNotFoundException
     * @throws ShootingSessionAccessDeniedException
     */
    public function getSummary(string $token, int $sessionId): ShootingSessionSummaryResponse
    {
        $session = $this->getOwnedSession($token, $sessionId);

        return $this->toSummaryResponse($session);
    }

    /**
     * @throws NoLinkedPlayerException
     * @throws ShootingSessionNotFoundException
     * @throws ShootingSessionAccessDeniedException
     * @throws ShootingSessionAlreadyFinishedException
     * @throws InvalidShootingSessionStructureException
     */
    public function complete(string $token, int $sessionId, CompleteShootingSessionRequest $req): ShootingSessionSummaryResponse
    {
        $session = $this->getOwnedSession($token, $sessionId);
        if ($session->isFinished()) {
            throw new ShootingSessionAlreadyFinishedException();
        }

        $shotInputs = $this->validateStructure($req);

        $this->em->wrapInTransaction(function () use ($session, $shotInputs): void {
            $total = 0;
            foreach ($shotInputs as [$workshop, $distance, $result]) {
                $score = $this->scoring->pointsFor($workshop, $result);
                $this->em->persist(new ShootingShot($session, $workshop, $distance, $result, $score));
                $total += $score;
            }
            $session->markFinished($total);
        });

        return $this->toSummaryResponse($session);
    }

    /**
     * @throws NoLinkedPlayerException
     * @throws ShootingSessionNotFoundException
     * @throws ShootingSessionAccessDeniedException
     * @throws ShootingSessionAlreadyFinishedException
     */
    public function abandon(string $token, int $sessionId): void
    {
        $session = $this->getOwnedSession($token, $sessionId);
        if ($session->isFinished()) {
            throw new ShootingSessionAlreadyFinishedException();
        }

        $this->em->remove($session);
        $this->em->flush();
    }

    /**
     * Optional free-form context (title/description) added by the player,
     * typically once the session is finished.
     *
     * @throws NoLinkedPlayerException
     * @throws ShootingSessionNotFoundException
     * @throws ShootingSessionAccessDeniedException
     */
    public function updateContext(string $token, int $sessionId, UpdateShootingSessionContextRequest $req): ShootingSessionSummaryResponse
    {
        $session = $this->getOwnedSession($token, $sessionId);

        $session->setContext(
            $this->resolveContextNature($req->contextNature),
            $this->normalizeOptionalString($req->title),
            $this->normalizeOptionalString($req->description),
        );
        $this->em->flush();

        return $this->toSummaryResponse($session);
    }

    /**
     * @throws NoLinkedPlayerException
     */
    public function history(string $token, int $page = 1, int $pageSize = 20): ShootingSessionHistoryResponse
    {
        $player = $this->resolveOwnPlayer($token);
        [$total, $sessions] = $this->sessions->findHistoryForPlayer((int) $player->getId(), $page, $pageSize);

        $items = array_map(
            static fn (ShootingSession $s): ShootingSessionHistoryItemResponse => new ShootingSessionHistoryItemResponse(
                id: (int) $s->getId(),
                createdAt: $s->getCreatedAt()->format(DATE_ATOM),
                finishedAt: (string) $s->getFinishedAt()?->format(DATE_ATOM),
                totalScore: (int) $s->getTotalScore(),
                contextNature: $s->getContextNature()?->value,
                title: $s->getTitle(),
            ),
            $sessions,
        );

        return new ShootingSessionHistoryResponse(page: $page, pageSize: $pageSize, total: $total, items: $items);
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
     * @throws NoLinkedPlayerException
     * @throws ShootingSessionNotFoundException
     * @throws ShootingSessionAccessDeniedException
     */
    private function getOwnedSession(string $token, int $sessionId): ShootingSession
    {
        $player = $this->resolveOwnPlayer($token);

        $session = $this->sessions->find($sessionId);
        if ($session === null) {
            throw new ShootingSessionNotFoundException();
        }

        if (!$session->belongsTo($player)) {
            throw new ShootingSessionAccessDeniedException();
        }

        return $session;
    }

    /**
     * Validates that the 20 submitted shots cover each of the 5 workshops
     * x 4 distances exactly once, and that each result is allowed for its
     * workshop (e.g. no "carreau" on the jack workshop).
     *
     * @return list<array{0:ShootingWorkshop,1:ShootingDistance,2:ShootingShotResult}>
     * @throws InvalidShootingSessionStructureException
     */
    private function validateStructure(CompleteShootingSessionRequest $req): array
    {
        $errors = [];
        $seen = [];
        $parsed = [];

        foreach ($req->shots as $index => $shotInput) {
            $workshop = ShootingWorkshop::tryFrom($shotInput->workshop);
            $distance = ShootingDistance::tryFrom($shotInput->distance);
            $result = ShootingShotResult::tryFrom($shotInput->result);

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
            foreach (ShootingWorkshop::all() as $workshop) {
                foreach (ShootingDistance::all() as $distance) {
                    $key = $workshop->value.'-'.$distance->value;
                    if (!isset($seen[$key])) {
                        $errors['shots'] = sprintf('Missing shot for workshop %d at %dm.', $workshop->value, $distance->value);
                    }
                }
            }
        }

        if ($errors !== []) {
            throw InvalidShootingSessionStructureException::withErrors($errors);
        }

        return $parsed;
    }

    private function toStartedResponse(ShootingSession $session): ShootingSessionStartedResponse
    {
        return new ShootingSessionStartedResponse(
            id: (int) $session->getId(),
            createdAt: $session->getCreatedAt()->format(DATE_ATOM),
        );
    }

    private function toSummaryResponse(ShootingSession $session): ShootingSessionSummaryResponse
    {
        $shots = $this->shots->findBySession($session);

        $byWorkshop = [];
        foreach ($shots as $shot) {
            $byWorkshop[$shot->getWorkshop()->value][] = $shot;
        }

        $workshops = [];
        foreach (ShootingWorkshop::all() as $workshop) {
            /** @var list<ShootingShot> $shotsForWorkshop */
            $shotsForWorkshop = $byWorkshop[$workshop->value] ?? [];
            $workshopTotal = 0;
            $shotSummaries = [];
            foreach ($shotsForWorkshop as $shot) {
                $workshopTotal += $shot->getScore();
                $shotSummaries[] = new ShootingShotSummary(
                    distance: $shot->getDistance()->value,
                    result: $shot->getResult()->value,
                    score: $shot->getScore(),
                );
            }
            $workshops[] = new ShootingWorkshopSummary(
                workshop: $workshop->value,
                totalScore: $workshopTotal,
                shots: $shotSummaries,
            );
        }

        return new ShootingSessionSummaryResponse(
            id: (int) $session->getId(),
            createdAt: $session->getCreatedAt()->format(DATE_ATOM),
            finishedAt: $session->getFinishedAt()?->format(DATE_ATOM),
            totalScore: $session->getTotalScore(),
            contextNature: $session->getContextNature()?->value,
            title: $session->getTitle(),
            description: $session->getDescription(),
            workshops: $workshops,
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

    private function resolveContextNature(?string $value): ?ShootingContextNature
    {
        if ($value === null || $value === '') {
            return null;
        }

        return ShootingContextNature::from($value);
    }
}

final class NoLinkedPlayerException extends \RuntimeException
{
}

final class ShootingSessionNotFoundException extends \RuntimeException
{
}

final class ShootingSessionAccessDeniedException extends \RuntimeException
{
}

final class ShootingSessionAlreadyFinishedException extends \RuntimeException
{
}

final class InvalidShootingSessionStructureException extends \RuntimeException
{
    /** @param array<string,string> $errors */
    public function __construct(public array $errors)
    {
        parent::__construct('Invalid shooting session structure.');
    }

    /** @param array<string,string> $errors */
    public static function withErrors(array $errors): self
    {
        return new self($errors);
    }
}
