<?php

declare(strict_types=1);

namespace App\Service\Rings;

use App\Dto\Response\RingsStatsEvolutionPointResponse;
use App\Dto\Response\RingsStatsResponse;
use App\Dto\Response\RingsStatsScoreDecileResponse;
use App\Dto\Response\RingsStatsSummaryResponse;
use App\Dto\Response\RingsStatsTargetResponse;
use App\Enum\RingsMode;
use App\Enum\RingsTarget;
use App\Repository\RingsSessionRepository;
use App\Repository\RingsThrowRepository;
use App\Service\PlayerViewContextResolver;
use App\ValueObject\DateRange;

final class RingsStatsService
{
    public function __construct(
        private PlayerViewContextResolver $playerViewContext,
        private RingsSessionRepository $sessions,
        private RingsThrowRepository $throws,
    ) {
    }

    public function stats(
        string $token,
        ?DateRange $dateRange = null,
        ?int $impersonatePlayerId = null,
        RingsMode $mode = RingsMode::POINT,
    ): RingsStatsResponse {
        $context = $this->playerViewContext->resolve($token, $impersonatePlayerId);
        $playerId = $context->playerId;

        if ($playerId === null) {
            return $this->emptyResponse($mode);
        }

        return $this->statsForPlayerId($playerId, $dateRange, $mode);
    }

    public function statsForPlayerId(int $playerId, ?DateRange $dateRange = null, RingsMode $mode = RingsMode::POINT): RingsStatsResponse
    {
        $sessionsCount = $this->sessions->countCompletedForPlayer($playerId, $mode, $dateRange);

        if ($sessionsCount === 0) {
            if ($dateRange !== null && $this->sessions->countCompletedForPlayer($playerId, $mode) > 0) {
                return $this->emptyResponse($mode, 'no_data_in_period');
            }

            return $this->emptyResponse($mode);
        }

        $totalThrows = $this->throws->countForPlayerCompletedSessions($playerId, $mode, $dateRange);
        $successCount = $this->throws->countSuccessesForPlayerCompletedSessions($playerId, $mode, $dateRange);
        $overallSuccessRate = $totalThrows > 0
            ? round($successCount / $totalThrows * 100, 1)
            : null;

        $evolution = array_map(
            static fn (array $row): RingsStatsEvolutionPointResponse => new RingsStatsEvolutionPointResponse(
                sessionId: $row['id'],
                date: $row['playedAt']->format(DATE_ATOM),
                totalScore: $row['totalScore'],
            ),
            $this->sessions->findEvolutionForPlayer($playerId, $mode, $dateRange),
        );

        $aggregatedByTarget = [];
        foreach ($this->throws->aggregateByTargetForPlayer($playerId, $mode, $dateRange) as $row) {
            $aggregatedByTarget[$row['target']] = $row;
        }

        $byTarget = [];
        $carreauCount = 0;
        foreach ([RingsTarget::FAR, RingsTarget::MIDDLE, RingsTarget::NEAR] as $target) {
            $row = $aggregatedByTarget[$target->value] ?? [
                'attemptCount' => 0,
                'successCount' => 0,
                'carreauCount' => 0,
                'sumScore' => 0,
            ];
            $attemptCount = (int) $row['attemptCount'];
            $targetSuccessCount = (int) $row['successCount'];
            $targetCarreauCount = (int) $row['carreauCount'];
            $carreauCount += $targetCarreauCount;
            $byTarget[] = new RingsStatsTargetResponse(
                target: $target->value,
                attemptCount: $attemptCount,
                successCount: $targetSuccessCount,
                successRate: $attemptCount > 0
                    ? round($targetSuccessCount / $attemptCount * 100, 1)
                    : 0.0,
                carreauCount: $targetCarreauCount,
                averagePointsPerAttempt: $attemptCount > 0
                    ? round((int) $row['sumScore'] / $attemptCount, 2)
                    : 0.0,
            );
        }

        $scoreByDecile = $this->toDecileResponses(
            (new RingsScoreDecileCalculator($mode->maxSessionScore()))->buildDistribution(
                $this->sessions->findCompletedSessionScoresForPlayer($playerId, $mode, $dateRange),
            ),
        );

        return new RingsStatsResponse(
            status: 'ok',
            summary: new RingsStatsSummaryResponse(
                sessionsCount: $sessionsCount,
                totalThrows: $totalThrows,
                averageSessionScore: $this->sessions->averageTotalScoreForPlayer($playerId, $mode, $dateRange),
                bestSessionScore: $this->sessions->bestTotalScoreForPlayer($playerId, $mode, $dateRange),
                overallSuccessRate: $overallSuccessRate,
                maxSessionScore: $mode->maxSessionScore(),
                carreauCount: $carreauCount,
                carreauRate: $totalThrows > 0 ? round($carreauCount / $totalThrows * 100, 1) : null,
            ),
            evolution: $evolution,
            byTarget: $byTarget,
            scoreByDecile: $scoreByDecile,
        );
    }

    /**
     * @param list<array{decile:int,minScore:int,maxScore:int,sessionCount:int}> $rows
     *
     * @return list<RingsStatsScoreDecileResponse>
     */
    private function toDecileResponses(array $rows): array
    {
        return array_map(
            static fn (array $row): RingsStatsScoreDecileResponse => new RingsStatsScoreDecileResponse(
                decile: $row['decile'],
                minScore: $row['minScore'],
                maxScore: $row['maxScore'],
                sessionCount: $row['sessionCount'],
            ),
            $rows,
        );
    }

    private function emptyResponse(RingsMode $mode, string $status = 'no_sessions'): RingsStatsResponse
    {
        return new RingsStatsResponse(
            status: $status,
            summary: new RingsStatsSummaryResponse(
                sessionsCount: 0,
                totalThrows: 0,
                averageSessionScore: null,
                bestSessionScore: null,
                overallSuccessRate: null,
                maxSessionScore: $mode->maxSessionScore(),
                carreauCount: 0,
                carreauRate: null,
            ),
            evolution: [],
            byTarget: [],
            scoreByDecile: $this->toDecileResponses(
                (new RingsScoreDecileCalculator($mode->maxSessionScore()))->buildDistribution([]),
            ),
        );
    }
}
