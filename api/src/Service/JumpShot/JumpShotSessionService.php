<?php

declare(strict_types=1);

namespace App\Service\JumpShot;

use App\Dto\Request\JumpShotThrowInputDto;
use App\Dto\Request\StartJumpShotSessionRequest;
use App\Dto\Request\SubmitJumpShotSeriesRequest;
use App\Dto\Response\JumpShotSeriesSummaryResponse;
use App\Dto\Response\JumpShotSessionHistoryItemResponse;
use App\Dto\Response\JumpShotSessionHistoryResponse;
use App\Dto\Response\JumpShotSessionStartedResponse;
use App\Dto\Response\JumpShotSessionSummaryResponse;
use App\Dto\Response\JumpShotThrowSummaryResponse;
use App\Dto\Response\RevertJumpShotLastSeriesResponse;
use App\Dto\Response\SubmitJumpShotSeriesResponse;
use App\Entity\JumpShotSeries;
use App\Entity\JumpShotSession;
use App\Entity\JumpShotThrow;
use App\Entity\Player;
use App\Enum\JumpShotProgressionMode;
use App\Enum\JumpShotThrowResult;
use App\Repository\JumpShotSeriesRepository;
use App\Repository\JumpShotSessionRepository;
use App\Repository\JumpShotThrowRepository;
use App\Repository\PlayerRepository;
use App\Service\Auth\CurrentUserService;
use App\Service\PlayerViewContextResolver;
use Doctrine\ORM\EntityManagerInterface;

final class JumpShotSessionService
{
    public function __construct(
        private CurrentUserService $currentUser,
        private PlayerViewContextResolver $playerViewContext,
        private PlayerRepository $players,
        private JumpShotSessionRepository $sessions,
        private JumpShotSeriesRepository $series,
        private JumpShotThrowRepository $throws,
        private JumpShotProgressionCalculator $progression,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @throws NoLinkedPlayerException
     */
    public function start(string $token, ?StartJumpShotSessionRequest $req = null): JumpShotSessionStartedResponse
    {
        $player = $this->resolveOwnPlayer($token);
        $mode = $this->resolveProgressionMode($req);

        $session = new JumpShotSession($player, $mode);
        $this->em->persist($session);
        $this->em->flush();

        return $this->toStartedResponse($session);
    }

    /**
     * @throws NoLinkedPlayerException
     */
    public function current(string $token): ?JumpShotSessionStartedResponse
    {
        $player = $this->resolveOwnPlayer($token);
        $session = $this->sessions->findInProgressForPlayer((int) $player->getId());

        return $session !== null ? $this->toStartedResponse($session) : null;
    }

    public function getSummary(string $token, int $sessionId, ?int $impersonatePlayerId = null): JumpShotSessionSummaryResponse
    {
        $session = $this->getViewableSession($token, $sessionId, $impersonatePlayerId);

        return $this->toSummaryResponse($session);
    }

    /**
     * @throws NoLinkedPlayerException
     * @throws JumpShotSessionNotFoundException
     * @throws JumpShotSessionAccessDeniedException
     * @throws JumpShotSessionAlreadyFinishedException
     * @throws InvalidJumpShotSeriesException
     */
    public function submitSeries(
        string $token,
        int $sessionId,
        SubmitJumpShotSeriesRequest $req,
    ): SubmitJumpShotSeriesResponse {
        $session = $this->getOwnedSession($token, $sessionId);
        if ($session->isFinished()) {
            throw new JumpShotSessionAlreadyFinishedException();
        }

        $resultsByPosition = $this->parseThrows($req);

        $seriesDistance = $session->getCurrentDistance();
        $evaluation = $this->progression->evaluate(
            $seriesDistance,
            $session->getCurrentDistance(),
            $resultsByPosition,
            $session->getProgressionMode(),
        );

        $seriesNumber = $this->series->countForSession($session) + 1;

        $seriesEntity = null;

        $this->em->wrapInTransaction(function () use (
            $session,
            $seriesDistance,
            $evaluation,
            $seriesNumber,
            $resultsByPosition,
            &$seriesEntity,
        ): void {
            $seriesEntity = new JumpShotSeries(
                $session,
                $seriesNumber,
                $seriesDistance,
                $evaluation->successCount,
                $evaluation->isSpecialSeries,
                $evaluation->consequence,
            );
            $this->em->persist($seriesEntity);

            foreach ($resultsByPosition as $position => $result) {
                $this->em->persist(new JumpShotThrow($seriesEntity, $position, $result));
            }

            if ($evaluation->sessionWon) {
                $session->markWon();
            } else {
                $session->setCurrentDistance($evaluation->nextDistance);
            }
        });

        \assert($seriesEntity instanceof JumpShotSeries);

        $seriesResponse = $this->toSeriesSummaryResponse($seriesEntity);
        $sessionResponse = $this->toSummaryResponse($session);

        return new SubmitJumpShotSeriesResponse(
            series: $seriesResponse,
            nextDistance: $evaluation->nextDistance->value,
            sessionWon: $evaluation->sessionWon,
            sessionFinished: $session->isFinished(),
            session: $sessionResponse,
        );
    }

    /**
     * @throws NoLinkedPlayerException
     * @throws JumpShotSessionNotFoundException
     * @throws JumpShotSessionAccessDeniedException
     * @throws JumpShotNoSeriesToRevertException
     */
    public function revertLastSeries(string $token, int $sessionId): RevertJumpShotLastSeriesResponse
    {
        $session = $this->getOwnedSession($token, $sessionId);
        $lastSeries = $this->series->findLastForSession($session);
        if ($lastSeries === null) {
            throw new JumpShotNoSeriesToRevertException();
        }

        $throwEntities = $this->throws->findAllForSeriesOrdered($lastSeries);
        $restoredThrows = array_map(
            static fn (JumpShotThrow $t): JumpShotThrowSummaryResponse => new JumpShotThrowSummaryResponse(
                position: $t->getPosition(),
                result: $t->getResult()->value,
            ),
            $throwEntities,
        );

        $seriesDistance = $lastSeries->getDistance();

        $this->em->wrapInTransaction(function () use ($session, $lastSeries, $seriesDistance): void {
            $this->em->remove($lastSeries);
            $session->reopenAfterSeriesRevert($seriesDistance);
        });

        return new RevertJumpShotLastSeriesResponse(
            session: $this->toSummaryResponse($session),
            restoredThrows: $restoredThrows,
        );
    }

    public function history(
        string $token,
        int $page = 1,
        int $pageSize = 20,
        ?int $impersonatePlayerId = null,
    ): JumpShotSessionHistoryResponse {
        $context = $this->playerViewContext->resolve($token, $impersonatePlayerId);
        if ($context->playerId === null) {
            return new JumpShotSessionHistoryResponse(page: $page, pageSize: $pageSize, total: 0, items: []);
        }

        [$total, $sessions] = $this->sessions->findHistoryForPlayer($context->playerId, $page, $pageSize);

        $items = array_map(function (JumpShotSession $session): JumpShotSessionHistoryItemResponse {
            $seriesCount = $this->series->countForSession($session);

            return new JumpShotSessionHistoryItemResponse(
                id: (int) $session->getId(),
                createdAt: $session->getCreatedAt()->format(DATE_ATOM),
                finishedAt: (string) $session->getFinishedAt()?->format(DATE_ATOM),
                seriesCount: $seriesCount,
                totalThrows: $seriesCount * 3,
            );
        }, $sessions);

        return new JumpShotSessionHistoryResponse(page: $page, pageSize: $pageSize, total: $total, items: $items);
    }

    /**
     * @throws NoLinkedPlayerException
     * @throws JumpShotSessionNotFoundException
     * @throws JumpShotSessionAccessDeniedException
     * @throws JumpShotSessionAlreadyFinishedException
     */
    public function abandon(string $token, int $sessionId): void
    {
        $session = $this->getOwnedSession($token, $sessionId);
        if ($session->isFinished()) {
            throw new JumpShotSessionAlreadyFinishedException();
        }

        $this->em->remove($session);
        $this->em->flush();
    }

    /**
     * @return array<int, JumpShotThrowResult>
     *
     * @throws InvalidJumpShotSeriesException
     */
    private function parseThrows(SubmitJumpShotSeriesRequest $req): array
    {
        $errors = [];
        $resultsByPosition = [];

        foreach ($req->throws as $index => $throwInput) {
            if (!$throwInput instanceof JumpShotThrowInputDto) {
                $errors["throws.$index"] = 'Invalid throw entry.';
                continue;
            }

            $position = $throwInput->position;
            if ($position === null || $position < 1 || $position > 3) {
                $errors["throws.$index.position"] = 'Position must be 1, 2 or 3.';

                continue;
            }

            if (isset($resultsByPosition[$position])) {
                $errors["throws.$index.position"] = 'Duplicate throw position in series.';

                continue;
            }

            $resultValue = $throwInput->result;
            if ($resultValue === null || $resultValue === '') {
                $errors["throws.$index.result"] = 'Result is required.';

                continue;
            }

            $result = JumpShotThrowResult::tryFrom($resultValue);
            if ($result === null) {
                $errors["throws.$index.result"] = 'Invalid throw result.';

                continue;
            }

            $resultsByPosition[$position] = $result;
        }

        for ($pos = 1; $pos <= 3; ++$pos) {
            if (!isset($resultsByPosition[$pos])) {
                $errors["throws.$pos"] = 'Missing throw for position '.$pos.'.';
            }
        }

        if ($errors !== []) {
            throw InvalidJumpShotSeriesException::withErrors($errors);
        }

        /** @var array<int, JumpShotThrowResult> $resultsByPosition */
        ksort($resultsByPosition);

        return $resultsByPosition;
    }

    private function resolveProgressionMode(?StartJumpShotSessionRequest $req): JumpShotProgressionMode
    {
        $value = $req?->progressionMode;
        if ($value === null || $value === '') {
            return JumpShotProgressionMode::STANDARD;
        }

        return JumpShotProgressionMode::from($value);
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
     * @throws JumpShotSessionNotFoundException
     * @throws JumpShotSessionAccessDeniedException
     */
    private function getViewableSession(string $token, int $sessionId, ?int $impersonatePlayerId): JumpShotSession
    {
        $context = $this->playerViewContext->resolve($token, $impersonatePlayerId);
        if ($context->playerId === null) {
            throw new JumpShotSessionAccessDeniedException();
        }

        $session = $this->sessions->find($sessionId);
        if ($session === null) {
            throw new JumpShotSessionNotFoundException();
        }

        if ((int) $session->getPlayer()->getId() !== $context->playerId) {
            throw new JumpShotSessionAccessDeniedException();
        }

        return $session;
    }

    /**
     * @throws NoLinkedPlayerException
     * @throws JumpShotSessionNotFoundException
     * @throws JumpShotSessionAccessDeniedException
     */
    private function getOwnedSession(string $token, int $sessionId): JumpShotSession
    {
        $player = $this->resolveOwnPlayer($token);
        $session = $this->sessions->find($sessionId);
        if ($session === null) {
            throw new JumpShotSessionNotFoundException();
        }

        if (!$session->belongsTo($player)) {
            throw new JumpShotSessionAccessDeniedException();
        }

        return $session;
    }

    private function toStartedResponse(JumpShotSession $session): JumpShotSessionStartedResponse
    {
        return new JumpShotSessionStartedResponse(
            id: (int) $session->getId(),
            createdAt: $session->getCreatedAt()->format(DATE_ATOM),
            currentDistance: $session->getCurrentDistance()->value,
            seriesCount: $this->series->countForSession($session),
            progressionMode: $session->getProgressionMode()->value,
        );
    }

    private function toSummaryResponse(JumpShotSession $session): JumpShotSessionSummaryResponse
    {
        $seriesEntities = $this->series->findAllForSessionOrdered($session);
        $seriesResponses = array_map(
            fn (JumpShotSeries $s): JumpShotSeriesSummaryResponse => $this->toSeriesSummaryResponse($s),
            $seriesEntities,
        );

        return new JumpShotSessionSummaryResponse(
            id: (int) $session->getId(),
            createdAt: $session->getCreatedAt()->format(DATE_ATOM),
            finishedAt: $session->getFinishedAt()?->format(DATE_ATOM),
            won: $session->isWon(),
            currentDistance: $session->getCurrentDistance()->value,
            seriesCount: count($seriesResponses),
            progressionMode: $session->getProgressionMode()->value,
            series: $seriesResponses,
        );
    }

    private function toSeriesSummaryResponse(JumpShotSeries $series): JumpShotSeriesSummaryResponse
    {
        $throwEntities = $this->throws->findAllForSeriesOrdered($series);
        $throws = array_map(
            static fn (JumpShotThrow $t): JumpShotThrowSummaryResponse => new JumpShotThrowSummaryResponse(
                position: $t->getPosition(),
                result: $t->getResult()->value,
            ),
            $throwEntities,
        );

        return new JumpShotSeriesSummaryResponse(
            seriesNumber: $series->getSeriesNumber(),
            distance: $series->getDistance()->value,
            successCount: $series->getSuccessCount(),
            isSpecialSeries: $series->isSpecialSeries(),
            consequence: $series->getConsequence()->value,
            createdAt: $series->getCreatedAt()->format(DATE_ATOM),
            throws: $throws,
        );
    }
}

final class NoLinkedPlayerException extends \RuntimeException
{
}

final class JumpShotSessionNotFoundException extends \RuntimeException
{
}

final class JumpShotSessionAccessDeniedException extends \RuntimeException
{
}

final class JumpShotSessionAlreadyFinishedException extends \RuntimeException
{
}

final class JumpShotNoSeriesToRevertException extends \RuntimeException
{
}

final class InvalidJumpShotSeriesException extends \RuntimeException
{
    /** @param array<string,string> $errors */
    public function __construct(public array $errors)
    {
        parent::__construct('Invalid jump shot series.');
    }

    /** @param array<string,string> $errors */
    public static function withErrors(array $errors): self
    {
        return new self($errors);
    }
}
