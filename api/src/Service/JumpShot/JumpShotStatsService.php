<?php

declare(strict_types=1);

namespace App\Service\JumpShot;

use App\Dto\Response\JumpShotStatsDistanceResponse;
use App\Dto\Response\JumpShotStatsEvolutionPointResponse;
use App\Dto\Response\JumpShotStatsResponse;
use App\Dto\Response\JumpShotStatsResultResponse;
use App\Dto\Response\JumpShotStatsSummaryResponse;
use App\Enum\JumpShotThrowResult;
use App\Repository\JumpShotSeriesRepository;
use App\Repository\JumpShotSessionRepository;
use App\Repository\JumpShotThrowRepository;
use App\Service\PlayerViewContextResolver;
use App\ValueObject\DateRange;

final class JumpShotStatsService
{
    public function __construct(
        private PlayerViewContextResolver $playerViewContext,
        private JumpShotSessionRepository $sessions,
        private JumpShotSeriesRepository $series,
        private JumpShotThrowRepository $throws,
    ) {
    }

    public function stats(string $token, ?DateRange $dateRange = null, ?int $impersonatePlayerId = null): JumpShotStatsResponse
    {
        $context = $this->playerViewContext->resolve($token, $impersonatePlayerId);
        $playerId = $context->playerId;

        if ($playerId === null) {
            return $this->emptyResponse();
        }

        return $this->statsForPlayerId($playerId, $dateRange);
    }

    public function statsForPlayerId(int $playerId, ?DateRange $dateRange = null): JumpShotStatsResponse
    {
        $sessionsCount = $this->sessions->countCompletedForPlayer($playerId, $dateRange);

        if ($sessionsCount === 0) {
            if ($dateRange !== null && $this->sessions->countCompletedForPlayer($playerId) > 0) {
                return $this->emptyResponse('no_data_in_period');
            }

            return $this->emptyResponse();
        }

        $totalSeries = $this->series->countForPlayerCompletedSessions($playerId, $dateRange);
        $totalThrows = $this->throws->countForPlayerCompletedSessions($playerId, $dateRange);
        $averageSeries = round($totalSeries / $sessionsCount, 1);
        $fewestSeries = $this->series->fewestSeriesAmongCompletedSessions($playerId, $dateRange);
        $specialCount = $this->series->countSpecialForPlayerCompletedSessions($playerId, $dateRange);

        $successTotal = 0;
        foreach ($this->throws->aggregateByResultForPlayer($playerId, $dateRange) as $row) {
            if ($row['result'] !== JumpShotThrowResult::MISSED->value) {
                $successTotal += $row['count'];
            }
        }
        $overallSuccessRate = $totalThrows > 0
            ? round($successTotal / $totalThrows * 100, 1)
            : null;

        $evolution = [];
        foreach ($this->sessions->findCompletedForPlayer($playerId, $dateRange) as $session) {
            $evolution[] = new JumpShotStatsEvolutionPointResponse(
                sessionId: (int) $session->getId(),
                date: (string) $session->getFinishedAt()?->format(DATE_ATOM),
                seriesCount: $this->series->countForSession($session),
            );
        }

        $byDistance = array_map(
            static fn (array $row): JumpShotStatsDistanceResponse => new JumpShotStatsDistanceResponse(
                distance: $row['distance'],
                throwCount: $row['throwCount'],
                successRate: $row['throwCount'] > 0
                    ? round($row['successCount'] / $row['throwCount'] * 100, 1)
                    : 0.0,
            ),
            $this->throws->aggregateByDistanceForPlayer($playerId, $dateRange),
        );

        $byResult = array_map(
            static fn (array $row): JumpShotStatsResultResponse => new JumpShotStatsResultResponse(
                result: $row['result'],
                count: $row['count'],
            ),
            $this->throws->aggregateByResultForPlayer($playerId, $dateRange),
        );

        return new JumpShotStatsResponse(
            status: 'ok',
            summary: new JumpShotStatsSummaryResponse(
                sessionsCount: $sessionsCount,
                totalSeries: $totalSeries,
                totalThrows: $totalThrows,
                averageSeriesPerSession: $averageSeries,
                fewestSeriesToWin: $fewestSeries,
                overallSuccessRate: $overallSuccessRate,
                specialSeriesCount: $specialCount,
            ),
            evolution: $evolution,
            byDistance: $byDistance,
            byResult: $byResult,
        );
    }

    private function emptyResponse(string $status = 'no_sessions'): JumpShotStatsResponse
    {
        return new JumpShotStatsResponse(
            status: $status,
            summary: new JumpShotStatsSummaryResponse(
                sessionsCount: 0,
                totalSeries: 0,
                totalThrows: 0,
                averageSeriesPerSession: null,
                fewestSeriesToWin: null,
                overallSuccessRate: null,
                specialSeriesCount: 0,
            ),
            evolution: [],
            byDistance: [],
            byResult: [],
        );
    }
}
