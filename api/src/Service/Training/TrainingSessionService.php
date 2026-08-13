<?php

declare(strict_types=1);

namespace App\Service\Training;

use App\Dto\Request\CreateTrainingSessionRequest;
use App\Dto\Request\RecordTrainingAttemptRequest;
use App\Dto\Response\RecordTrainingAttemptResponse;
use App\Dto\Response\TrainingAttemptSummary;
use App\Dto\Response\TrainingSessionHistoryItemResponse;
use App\Dto\Response\TrainingSessionHistoryResponse;
use App\Dto\Response\TrainingSessionStartedResponse;
use App\Dto\Response\TrainingSessionSummaryResponse;
use App\Entity\Player;
use App\Entity\TrainingAttempt;
use App\Entity\TrainingSession;
use App\Enum\TrainingType;
use App\Repository\PlayerRepository;
use App\Repository\TrainingAttemptRepository;
use App\Repository\TrainingSessionRepository;
use App\Service\Auth\CurrentUserService;
use Doctrine\ORM\EntityManagerInterface;

final class TrainingSessionService
{
    public function __construct(
        private CurrentUserService $currentUser,
        private PlayerRepository $players,
        private TrainingSessionRepository $sessions,
        private TrainingAttemptRepository $attempts,
        private TrainingScoreCalculator $scoring,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @throws NoLinkedPlayerException
     */
    public function create(string $token, CreateTrainingSessionRequest $req): TrainingSessionStartedResponse
    {
        $player = $this->resolveOwnPlayer($token);
        $type = TrainingType::from($req->type);

        $session = new TrainingSession($player, $type, $req->distance, $req->plannedBalls);
        $this->em->persist($session);
        $this->em->flush();

        return $this->toStartedResponse($session);
    }

    /**
     * @throws NoLinkedPlayerException
     */
    public function current(string $token): ?TrainingSessionStartedResponse
    {
        $player = $this->resolveOwnPlayer($token);
        $session = $this->sessions->findInProgressForPlayer((int) $player->getId());

        return $session !== null ? $this->toStartedResponse($session) : null;
    }

    /**
     * @throws NoLinkedPlayerException
     * @throws TrainingSessionNotFoundException
     * @throws TrainingSessionAccessDeniedException
     */
    public function getSummary(string $token, int $sessionId): TrainingSessionSummaryResponse
    {
        $session = $this->getOwnedSession($token, $sessionId);

        return $this->toSummaryResponse($session);
    }

    /**
     * @throws NoLinkedPlayerException
     * @throws TrainingSessionNotFoundException
     * @throws TrainingSessionAccessDeniedException
     * @throws TrainingSessionAlreadyFinishedException
     * @throws InvalidTrainingAttemptException
     */
    public function recordAttempt(
        string $token,
        int $sessionId,
        RecordTrainingAttemptRequest $req,
    ): RecordTrainingAttemptResponse {
        $session = $this->getOwnedSession($token, $sessionId);
        if ($session->isFinished()) {
            throw new TrainingSessionAlreadyFinishedException();
        }

        $attemptsCount = $this->attempts->countForSession($session);
        if ($attemptsCount >= $session->getPlannedBalls()) {
            throw new TrainingSessionAlreadyFinishedException();
        }

        if (!$this->scoring->isResultAllowed($session->getType(), $req->result)) {
            throw InvalidTrainingAttemptException::withErrors(['result' => 'Invalid result for this training type.']);
        }

        $score = $this->scoring->scoreFor($session->getType(), $req->result);
        $number = $attemptsCount + 1;

        $attempt = new TrainingAttempt(
            $session,
            $number,
            $session->getType(),
            $session->getDistance(),
            $req->result,
            $score,
        );

        $this->em->persist($attempt);
        $this->em->flush();

        $currentScore = $this->attempts->sumScoreForSession($session);
        $newAttemptsCount = $this->attempts->countForSession($session);
        $sessionFinished = false;
        $summary = null;

        if ($newAttemptsCount >= $session->getPlannedBalls()) {
            $session->markFinished($currentScore);
            $this->em->flush();
            $sessionFinished = true;
            $summary = $this->toSummaryResponse($session);
        }

        return new RecordTrainingAttemptResponse(
            number: $number,
            result: $req->result,
            score: $score,
            currentScore: $currentScore,
            attemptsCount: $newAttemptsCount,
            sessionFinished: $sessionFinished,
            summary: $summary,
        );
    }

    /**
     * @throws NoLinkedPlayerException
     * @throws TrainingSessionNotFoundException
     * @throws TrainingSessionAccessDeniedException
     * @throws TrainingSessionAlreadyFinishedException
     */
    public function abandon(string $token, int $sessionId): void
    {
        $session = $this->getOwnedSession($token, $sessionId);
        if ($session->isFinished()) {
            throw new TrainingSessionAlreadyFinishedException();
        }

        $this->em->remove($session);
        $this->em->flush();
    }

    /**
     * @throws NoLinkedPlayerException
     */
    public function history(string $token, int $page = 1, int $pageSize = 20): TrainingSessionHistoryResponse
    {
        $player = $this->resolveOwnPlayer($token);
        [$total, $sessions] = $this->sessions->findHistoryForPlayer((int) $player->getId(), $page, $pageSize);

        $items = array_map(
            fn (TrainingSession $s): TrainingSessionHistoryItemResponse => $this->toHistoryItem($s),
            $sessions,
        );

        return new TrainingSessionHistoryResponse(page: $page, pageSize: $pageSize, total: $total, items: $items);
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
     * @throws TrainingSessionNotFoundException
     * @throws TrainingSessionAccessDeniedException
     */
    private function getOwnedSession(string $token, int $sessionId): TrainingSession
    {
        $player = $this->resolveOwnPlayer($token);

        $session = $this->sessions->find($sessionId);
        if ($session === null) {
            throw new TrainingSessionNotFoundException();
        }

        if (!$session->belongsTo($player)) {
            throw new TrainingSessionAccessDeniedException();
        }

        return $session;
    }

    private function toStartedResponse(TrainingSession $session): TrainingSessionStartedResponse
    {
        $attemptsCount = $this->attempts->countForSession($session);
        $currentScore = $this->attempts->sumScoreForSession($session);

        return new TrainingSessionStartedResponse(
            id: (int) $session->getId(),
            type: $session->getType()->value,
            distance: $session->getDistance(),
            plannedBalls: $session->getPlannedBalls(),
            createdAt: $session->getCreatedAt()->format(DATE_ATOM),
            attemptsCount: $attemptsCount,
            currentScore: $currentScore,
        );
    }

    private function toSummaryResponse(TrainingSession $session): TrainingSessionSummaryResponse
    {
        $attemptEntities = $this->attempts->findBySession($session);
        $attempts = array_map(
            static fn (TrainingAttempt $a): TrainingAttemptSummary => new TrainingAttemptSummary(
                number: $a->getNumber(),
                result: $a->getResult(),
                score: $a->getScore(),
            ),
            $attemptEntities,
        );

        $attemptsCount = count($attemptEntities);
        $successfulBalls = $this->attempts->countSuccessfulForSession($session);
        $successRate = $attemptsCount > 0
            ? round($successfulBalls / $attemptsCount * 100, 1)
            : null;

        return new TrainingSessionSummaryResponse(
            id: (int) $session->getId(),
            type: $session->getType()->value,
            distance: $session->getDistance(),
            plannedBalls: $session->getPlannedBalls(),
            createdAt: $session->getCreatedAt()->format(DATE_ATOM),
            finishedAt: $session->getFinishedAt()?->format(DATE_ATOM),
            totalScore: $session->getTotalScore(),
            successfulBalls: $successfulBalls,
            successRate: $successRate,
            attempts: $attempts,
        );
    }

    private function toHistoryItem(TrainingSession $session): TrainingSessionHistoryItemResponse
    {
        $attemptsCount = $this->attempts->countForSession($session);
        $successfulBalls = $this->attempts->countSuccessfulForSession($session);
        $successRate = $attemptsCount > 0
            ? round($successfulBalls / $attemptsCount * 100, 1)
            : 0.0;

        return new TrainingSessionHistoryItemResponse(
            id: (int) $session->getId(),
            type: $session->getType()->value,
            distance: $session->getDistance(),
            plannedBalls: $session->getPlannedBalls(),
            createdAt: $session->getCreatedAt()->format(DATE_ATOM),
            finishedAt: (string) $session->getFinishedAt()?->format(DATE_ATOM),
            totalScore: (int) $session->getTotalScore(),
            successfulBalls: $successfulBalls,
            successRate: $successRate,
        );
    }
}

final class NoLinkedPlayerException extends \RuntimeException
{
}

final class TrainingSessionNotFoundException extends \RuntimeException
{
}

final class TrainingSessionAccessDeniedException extends \RuntimeException
{
}

final class TrainingSessionAlreadyFinishedException extends \RuntimeException
{
}

final class InvalidTrainingAttemptException extends \RuntimeException
{
    /** @param array<string,string> $errors */
    public function __construct(public array $errors)
    {
        parent::__construct('Invalid training attempt.');
    }

    /** @param array<string,string> $errors */
    public static function withErrors(array $errors): self
    {
        return new self($errors);
    }
}
