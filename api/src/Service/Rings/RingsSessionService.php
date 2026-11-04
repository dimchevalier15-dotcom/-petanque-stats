<?php

declare(strict_types=1);

namespace App\Service\Rings;

use App\Dto\Request\CompleteRingsSessionRequest;
use App\Dto\Request\StartRingsSessionRequest;
use App\Dto\Response\RingsSessionHistoryItemResponse;
use App\Dto\Response\RingsSessionHistoryResponse;
use App\Dto\Response\RingsSessionStartedResponse;
use App\Dto\Response\RingsSessionSummaryResponse;
use App\Dto\Response\RingsThrowSummaryResponse;
use App\Entity\Player;
use App\Entity\RingsSession;
use App\Entity\RingsThrow;
use App\Enum\RingsMode;
use App\Enum\RingsTarget;
use App\Repository\PlayerRepository;
use App\Repository\RingsSessionRepository;
use App\Repository\RingsThrowRepository;
use App\Service\Auth\CurrentUserService;
use App\Service\PlayerViewContextResolver;
use Doctrine\ORM\EntityManagerInterface;

final class RingsSessionService
{
    public const THROWS_PER_SESSION = 15;

    public function __construct(
        private CurrentUserService $currentUser,
        private PlayerViewContextResolver $playerViewContext,
        private PlayerRepository $players,
        private RingsSessionRepository $sessions,
        private RingsThrowRepository $throws,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @throws NoLinkedPlayerException
     */
    public function start(string $token, ?StartRingsSessionRequest $req = null): RingsSessionStartedResponse
    {
        $player = $this->resolveOwnPlayer($token);
        $mode = $req?->mode !== null ? RingsMode::from($req->mode) : RingsMode::POINT;

        $session = new RingsSession($player, $mode);
        $this->em->persist($session);
        $this->em->flush();

        return $this->toStartedResponse($session);
    }

    /**
     * @throws NoLinkedPlayerException
     */
    public function current(string $token): ?RingsSessionStartedResponse
    {
        $player = $this->resolveOwnPlayer($token);
        $session = $this->sessions->findInProgressForPlayer((int) $player->getId());

        return $session !== null ? $this->toStartedResponse($session) : null;
    }

    /**
     * @throws RingsSessionNotFoundException
     * @throws RingsSessionAccessDeniedException
     */
    public function getSummary(string $token, int $sessionId, ?int $impersonatePlayerId = null): RingsSessionSummaryResponse
    {
        return $this->toSummaryResponse($this->getViewableSession($token, $sessionId, $impersonatePlayerId));
    }

    /**
     * @throws NoLinkedPlayerException
     * @throws RingsSessionNotFoundException
     * @throws RingsSessionAccessDeniedException
     * @throws RingsSessionAlreadyFinishedException
     * @throws InvalidRingsSessionStructureException
     */
    public function complete(string $token, int $sessionId, CompleteRingsSessionRequest $req): RingsSessionSummaryResponse
    {
        $session = $this->getOwnedSession($token, $sessionId);
        if ($session->isFinished()) {
            throw new RingsSessionAlreadyFinishedException();
        }

        $parsed = $this->parseThrows($req);

        $this->em->wrapInTransaction(function () use ($session, $parsed): void {
            $total = 0;
            foreach ($parsed as $index => [$target, $success]) {
                $throw = new RingsThrow($session, $index + 1, $target, $success);
                $this->em->persist($throw);
                $total += $throw->getScore();
            }
            $session->markFinished($total);
        });

        return $this->toSummaryResponse($session);
    }

    /**
     * @throws NoLinkedPlayerException
     * @throws RingsSessionNotFoundException
     * @throws RingsSessionAccessDeniedException
     * @throws RingsSessionAlreadyFinishedException
     */
    public function abandon(string $token, int $sessionId): void
    {
        $session = $this->getOwnedSession($token, $sessionId);
        if ($session->isFinished()) {
            throw new RingsSessionAlreadyFinishedException();
        }

        $this->em->remove($session);
        $this->em->flush();
    }

    public function history(string $token, int $page = 1, int $pageSize = 20, ?int $impersonatePlayerId = null): RingsSessionHistoryResponse
    {
        $context = $this->playerViewContext->resolve($token, $impersonatePlayerId);
        if ($context->playerId === null) {
            return new RingsSessionHistoryResponse(page: $page, pageSize: $pageSize, total: 0, items: []);
        }

        [$total, $sessions] = $this->sessions->findHistoryForPlayer($context->playerId, $page, $pageSize);

        $items = array_map(
            static fn (RingsSession $s): RingsSessionHistoryItemResponse => new RingsSessionHistoryItemResponse(
                id: (int) $s->getId(),
                mode: $s->getMode()->value,
                playedAt: $s->getPlayedAt()->format(DATE_ATOM),
                finishedAt: (string) $s->getFinishedAt()?->format(DATE_ATOM),
                totalScore: (int) $s->getTotalScore(),
            ),
            $sessions,
        );

        return new RingsSessionHistoryResponse(page: $page, pageSize: $pageSize, total: $total, items: $items);
    }

    /**
     * @return list<array{0:RingsTarget,1:bool}>
     * @throws InvalidRingsSessionStructureException
     */
    private function parseThrows(CompleteRingsSessionRequest $req): array
    {
        if (\count($req->throws) !== self::THROWS_PER_SESSION) {
            throw new InvalidRingsSessionStructureException(['throws' => sprintf('Exactly %d throws are required.', self::THROWS_PER_SESSION)]);
        }

        $errors = [];
        $parsed = [];
        foreach ($req->throws as $index => $input) {
            $target = RingsTarget::tryFrom((string) $input->target);
            if ($target === null || $input->success === null) {
                $errors["throws[$index]"] = 'Invalid target or success.';
                continue;
            }
            $parsed[] = [$target, $input->success];
        }

        if ($errors !== []) {
            throw new InvalidRingsSessionStructureException($errors);
        }

        return $parsed;
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
     * @throws RingsSessionNotFoundException
     * @throws RingsSessionAccessDeniedException
     */
    private function getViewableSession(string $token, int $sessionId, ?int $impersonatePlayerId): RingsSession
    {
        $context = $this->playerViewContext->resolve($token, $impersonatePlayerId);
        if ($context->playerId === null) {
            throw new RingsSessionAccessDeniedException();
        }

        $session = $this->sessions->find($sessionId);
        if ($session === null) {
            throw new RingsSessionNotFoundException();
        }

        if ((int) $session->getPlayer()->getId() !== $context->playerId) {
            throw new RingsSessionAccessDeniedException();
        }

        return $session;
    }

    /**
     * @throws NoLinkedPlayerException
     * @throws RingsSessionNotFoundException
     * @throws RingsSessionAccessDeniedException
     */
    private function getOwnedSession(string $token, int $sessionId): RingsSession
    {
        $player = $this->resolveOwnPlayer($token);

        $session = $this->sessions->find($sessionId);
        if ($session === null) {
            throw new RingsSessionNotFoundException();
        }

        if (!$session->belongsTo($player)) {
            throw new RingsSessionAccessDeniedException();
        }

        return $session;
    }

    private function toStartedResponse(RingsSession $session): RingsSessionStartedResponse
    {
        return new RingsSessionStartedResponse(
            id: (int) $session->getId(),
            mode: $session->getMode()->value,
            createdAt: $session->getCreatedAt()->format(DATE_ATOM),
        );
    }

    private function toSummaryResponse(RingsSession $session): RingsSessionSummaryResponse
    {
        $throws = array_map(
            static fn (RingsThrow $t): RingsThrowSummaryResponse => new RingsThrowSummaryResponse(
                position: $t->getPosition(),
                target: $t->getTarget()->value,
                success: $t->isSuccess(),
                score: $t->getScore(),
            ),
            $this->throws->findBySession($session),
        );

        return new RingsSessionSummaryResponse(
            id: (int) $session->getId(),
            mode: $session->getMode()->value,
            createdAt: $session->getCreatedAt()->format(DATE_ATOM),
            playedAt: $session->getPlayedAt()->format(DATE_ATOM),
            finishedAt: $session->getFinishedAt()?->format(DATE_ATOM),
            totalScore: $session->getTotalScore(),
            throws: $throws,
        );
    }
}

final class RingsSessionNotFoundException extends \RuntimeException
{
}

final class RingsSessionAccessDeniedException extends \RuntimeException
{
}

final class RingsSessionAlreadyFinishedException extends \RuntimeException
{
}

final class InvalidRingsSessionStructureException extends \RuntimeException
{
    /** @param array<string,string> $errors */
    public function __construct(public array $errors)
    {
        parent::__construct('Invalid rings session structure.');
    }
}

final class NoLinkedPlayerException extends \RuntimeException
{
}
