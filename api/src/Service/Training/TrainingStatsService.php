<?php

declare(strict_types=1);

namespace App\Service\Training;

use App\Dto\Response\TrainingStatsDistanceResponse;
use App\Dto\Response\TrainingStatsEvolutionPointResponse;
use App\Dto\Response\TrainingStatsResponse;
use App\Dto\Response\TrainingStatsSummaryResponse;
use App\Dto\Response\TrainingStatsTypeResponse;
use App\Enum\TrainingType;
use App\Repository\PlayerRepository;
use App\Repository\TrainingAttemptRepository;
use App\Repository\TrainingSessionRepository;
use App\Service\Auth\CurrentUserService;
use App\ValueObject\DateRange;

final class TrainingStatsService
{
    public function __construct(
        private CurrentUserService $currentUser,
        private PlayerRepository $players,
        private TrainingSessionRepository $sessions,
        private TrainingAttemptRepository $attempts,
    ) {
    }

    /**
     * @throws NoLinkedPlayerException
     */
    public function stats(string $token, ?TrainingType $type = null, ?DateRange $dateRange = null): TrainingStatsResponse
    {
        $user = $this->currentUser->getUserFromToken($token);
        $player = $this->players->findOneByUserId((int) $user->getId());
        if ($player === null) {
            throw new NoLinkedPlayerException();
        }

        $playerId = (int) $player->getId();
        $sessionsCount = $this->sessions->countCompletedForPlayer($playerId, $type, $dateRange);

        if ($sessionsCount === 0) {
            if ($dateRange !== null && $this->sessions->countCompletedForPlayer($playerId, $type) > 0) {
                return $this->emptyResponse('no_data_in_period');
            }

            return $this->emptyResponse();
        }

        $totalBalls = $this->attempts->countForPlayer($playerId, $type, $dateRange);
        $successfulBalls = $this->attempts->countSuccessfulForPlayer($playerId, $type, $dateRange);
        $successRate = $totalBalls > 0
            ? round($successfulBalls / $totalBalls * 100, 1)
            : null;

        $bestScore = $this->sessions->bestTotalScoreForPlayer($playerId, $type, $dateRange);
        $averageScore = $this->sessions->averageTotalScoreForPlayer($playerId, $type, $dateRange);

        $evolution = [];
        foreach ($this->sessions->findEvolutionForPlayer($playerId, $type, $dateRange) as $row) {
            $sessionId = $row['id'];
            $session = $this->sessions->find($sessionId);
            if ($session === null) {
                continue;
            }
            $ballCount = $this->attempts->countForSession($session);
            $successful = $this->attempts->countSuccessfulForSession($session);
            $rate = $ballCount > 0 ? round($successful / $ballCount * 100, 1) : 0.0;

            $evolution[] = new TrainingStatsEvolutionPointResponse(
                sessionId: $sessionId,
                date: $row['finishedAt']->format(DATE_ATOM),
                totalScore: $row['totalScore'],
                plannedBalls: $row['plannedBalls'],
                successRate: $rate,
            );
        }

        $byType = array_map(
            static fn (array $row): TrainingStatsTypeResponse => new TrainingStatsTypeResponse(
                type: $row['type'],
                ballCount: $row['ballCount'],
                successRate: $row['ballCount'] > 0
                    ? round($row['successfulCount'] / $row['ballCount'] * 100, 1)
                    : 0.0,
                averageScore: $row['ballCount'] > 0
                    ? round($row['sumScore'] / $row['ballCount'], 2)
                    : 0.0,
            ),
            $this->attempts->aggregateByTypeForPlayer($playerId, $dateRange),
        );

        $byDistance = array_map(
            static fn (array $row): TrainingStatsDistanceResponse => new TrainingStatsDistanceResponse(
                distance: $row['distance'],
                ballCount: $row['ballCount'],
                successRate: $row['ballCount'] > 0
                    ? round($row['successfulCount'] / $row['ballCount'] * 100, 1)
                    : 0.0,
                averageScore: $row['ballCount'] > 0
                    ? round($row['sumScore'] / $row['ballCount'], 2)
                    : 0.0,
            ),
            $this->attempts->aggregateByDistanceForPlayer($playerId, $type, $dateRange),
        );

        return new TrainingStatsResponse(
            status: 'ok',
            summary: new TrainingStatsSummaryResponse(
                sessionsCount: $sessionsCount,
                totalBalls: $totalBalls,
                successfulBalls: $successfulBalls,
                successRate: $successRate,
                bestScore: $bestScore,
                averageScore: $averageScore,
            ),
            evolution: $evolution,
            byType: $byType,
            byDistance: $byDistance,
        );
    }

    private function emptyResponse(string $status = 'no_sessions'): TrainingStatsResponse
    {
        return new TrainingStatsResponse(
            status: $status,
            summary: new TrainingStatsSummaryResponse(
                sessionsCount: 0,
                totalBalls: 0,
                successfulBalls: 0,
                successRate: null,
                bestScore: null,
                averageScore: null,
            ),
            evolution: [],
            byType: [],
            byDistance: [],
        );
    }
}
