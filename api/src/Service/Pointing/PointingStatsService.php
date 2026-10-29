<?php

declare(strict_types=1);

namespace App\Service\Pointing;

use App\Dto\Response\PointingStatsCellResponse;
use App\Dto\Response\PointingStatsDistanceResponse;
use App\Dto\Response\PointingStatsEvolutionPointResponse;
use App\Dto\Response\PointingStatsResponse;
use App\Dto\Response\PointingStatsResultResponse;
use App\Dto\Response\PointingStatsSummaryResponse;
use App\Dto\Response\PointingStatsWorkshopResponse;
use App\Enum\ShootingContextNature;
use App\Repository\PointingSessionRepository;
use App\Repository\PointingShotRepository;
use App\Service\PlayerViewContextResolver;
use App\ValueObject\DateRange;

final class PointingStatsService
{
    public function __construct(
        private PlayerViewContextResolver $playerViewContext,
        private PointingSessionRepository $sessions,
        private PointingShotRepository $shots,
    ) {
    }

    public function stats(
        string $token,
        ?ShootingContextNature $contextNature = null,
        ?DateRange $dateRange = null,
        ?int $impersonatePlayerId = null,
    ): PointingStatsResponse {
        $context = $this->playerViewContext->resolve($token, $impersonatePlayerId);
        $playerId = $context->playerId;

        if ($playerId === null) {
            return $this->emptyResponse();
        }

        return $this->statsForPlayerId($playerId, $contextNature, $dateRange);
    }

    public function statsForPlayerId(
        int $playerId,
        ?ShootingContextNature $contextNature = null,
        ?DateRange $dateRange = null,
    ): PointingStatsResponse {
        $sessionsCount = $this->sessions->countCompletedForPlayer($playerId, $contextNature, $dateRange);

        if ($sessionsCount === 0) {
            if ($dateRange !== null && $this->sessions->countCompletedForPlayer($playerId, $contextNature) > 0) {
                return $this->emptyResponse('no_data_in_period');
            }

            if ($contextNature !== null && $this->sessions->countCompletedForPlayer($playerId, null, $dateRange) > 0) {
                return $this->emptyResponse('no_data_in_period');
            }

            return $this->emptyResponse();
        }

        $totalShots = $this->shots->countShotsForPlayer($playerId, $contextNature, $dateRange);
        $bestScore = $this->sessions->bestTotalScoreForPlayer($playerId, $contextNature, $dateRange);
        $averageSessionScore = $this->sessions->averageTotalScoreForPlayer($playerId, $contextNature, $dateRange);

        $evolution = array_map(
            static fn (array $row): PointingStatsEvolutionPointResponse => new PointingStatsEvolutionPointResponse(
                sessionId: $row['id'],
                date: $row['playedAt']->format(DATE_ATOM),
                totalScore: $row['totalScore'],
            ),
            $this->sessions->findEvolutionForPlayer($playerId, $contextNature, $dateRange),
        );

        $byWorkshop = array_map(
            static fn (array $row): PointingStatsWorkshopResponse => new PointingStatsWorkshopResponse(
                workshop: $row['workshop'],
                shotCount: $row['shotCount'],
                averageScore: round($row['sumScore'] / $row['shotCount'], 2),
            ),
            $this->shots->aggregateByWorkshopForPlayer($playerId, $contextNature, $dateRange),
        );

        $byDistance = array_map(
            static fn (array $row): PointingStatsDistanceResponse => new PointingStatsDistanceResponse(
                distance: $row['distance'],
                shotCount: $row['shotCount'],
                averageScore: round($row['sumScore'] / $row['shotCount'], 2),
            ),
            $this->shots->aggregateByDistanceForPlayer($playerId, $contextNature, $dateRange),
        );

        $byResult = array_map(
            static fn (array $row): PointingStatsResultResponse => new PointingStatsResultResponse(
                result: $row['result'],
                count: $row['count'],
            ),
            $this->shots->aggregateByResultForPlayer($playerId, $contextNature, $dateRange),
        );

        $heatmap = array_map(
            static fn (array $row): PointingStatsCellResponse => new PointingStatsCellResponse(
                workshop: $row['workshop'],
                distance: $row['distance'],
                shotCount: $row['shotCount'],
                averageScore: round($row['sumScore'] / $row['shotCount'], 2),
            ),
            $this->shots->aggregateByWorkshopAndDistanceForPlayer($playerId, $contextNature, $dateRange),
        );

        return new PointingStatsResponse(
            status: 'ok',
            summary: new PointingStatsSummaryResponse(
                sessionsCount: $sessionsCount,
                totalShots: $totalShots,
                averageSessionScore: $averageSessionScore,
                bestSessionScore: $bestScore,
            ),
            evolution: $evolution,
            byWorkshop: $byWorkshop,
            byDistance: $byDistance,
            byResult: $byResult,
            heatmap: $heatmap,
        );
    }

    private function emptyResponse(string $status = 'no_sessions'): PointingStatsResponse
    {
        return new PointingStatsResponse(
            status: $status,
            summary: new PointingStatsSummaryResponse(
                sessionsCount: 0,
                totalShots: 0,
                averageSessionScore: null,
                bestSessionScore: null,
            ),
            evolution: [],
            byWorkshop: [],
            byDistance: [],
            byResult: [],
            heatmap: [],
        );
    }
}
