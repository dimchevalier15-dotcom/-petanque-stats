<?php

declare(strict_types=1);

namespace App\Service\Shooting;

use App\Dto\Response\ShootingStatsCellResponse;
use App\Dto\Response\ShootingStatsDistanceResponse;
use App\Dto\Response\ShootingStatsEvolutionPointResponse;
use App\Dto\Response\ShootingStatsResponse;
use App\Dto\Response\ShootingStatsResultResponse;
use App\Dto\Response\ShootingStatsSummaryResponse;
use App\Dto\Response\ShootingStatsWorkshopResponse;
use App\Enum\ShootingContextNature;
use App\Repository\PlayerRepository;
use App\Repository\ShootingSessionRepository;
use App\Repository\ShootingShotRepository;
use App\Service\Auth\CurrentUserService;
use App\ValueObject\DateRange;

final class ShootingStatsService
{
    public function __construct(
        private CurrentUserService $currentUser,
        private PlayerRepository $players,
        private ShootingSessionRepository $sessions,
        private ShootingShotRepository $shots,
    ) {
    }

    /**
     * @throws NoLinkedPlayerException
     */
    public function stats(string $token, ?ShootingContextNature $contextNature = null, ?DateRange $dateRange = null): ShootingStatsResponse
    {
        $user = $this->currentUser->getUserFromToken($token);
        $player = $this->players->findOneByUserId((int) $user->getId());
        if ($player === null) {
            throw new NoLinkedPlayerException();
        }

        $playerId = (int) $player->getId();
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
            static fn (array $row): ShootingStatsEvolutionPointResponse => new ShootingStatsEvolutionPointResponse(
                sessionId: $row['id'],
                date: $row['finishedAt']->format(DATE_ATOM),
                totalScore: $row['totalScore'],
            ),
            $this->sessions->findEvolutionForPlayer($playerId, $contextNature, $dateRange),
        );

        $byWorkshop = array_map(
            static fn (array $row): ShootingStatsWorkshopResponse => new ShootingStatsWorkshopResponse(
                workshop: $row['workshop'],
                shotCount: $row['shotCount'],
                averageScore: round($row['sumScore'] / $row['shotCount'], 2),
            ),
            $this->shots->aggregateByWorkshopForPlayer($playerId, $contextNature, $dateRange),
        );

        $byDistance = array_map(
            static fn (array $row): ShootingStatsDistanceResponse => new ShootingStatsDistanceResponse(
                distance: $row['distance'],
                shotCount: $row['shotCount'],
                averageScore: round($row['sumScore'] / $row['shotCount'], 2),
            ),
            $this->shots->aggregateByDistanceForPlayer($playerId, $contextNature, $dateRange),
        );

        $byResult = array_map(
            static fn (array $row): ShootingStatsResultResponse => new ShootingStatsResultResponse(
                result: $row['result'],
                count: $row['count'],
            ),
            $this->shots->aggregateByResultForPlayer($playerId, $contextNature, $dateRange),
        );

        $heatmap = array_map(
            static fn (array $row): ShootingStatsCellResponse => new ShootingStatsCellResponse(
                workshop: $row['workshop'],
                distance: $row['distance'],
                shotCount: $row['shotCount'],
                averageScore: round($row['sumScore'] / $row['shotCount'], 2),
            ),
            $this->shots->aggregateByWorkshopAndDistanceForPlayer($playerId, $contextNature, $dateRange),
        );

        return new ShootingStatsResponse(
            status: 'ok',
            summary: new ShootingStatsSummaryResponse(
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

    private function emptyResponse(string $status = 'no_sessions'): ShootingStatsResponse
    {
        return new ShootingStatsResponse(
            status: $status,
            summary: new ShootingStatsSummaryResponse(
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
