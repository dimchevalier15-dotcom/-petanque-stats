<?php

declare(strict_types=1);

namespace App\Http;

use App\Enum\GameType;
use App\Enum\MatchNature;
use App\ValueObject\MatchHistoryFilters;
use Symfony\Component\HttpFoundation\Request;

final class MatchHistoryFiltersResolver
{
    /**
     * @return MatchHistoryFilters|array{message: string}
     */
    public static function fromRequest(Request $request): MatchHistoryFilters|array
    {
        try {
            $range = StatsDateRangeResolver::fromRequest($request);
        } catch (\InvalidArgumentException $e) {
            return ['message' => $e->getMessage()];
        }

        $nature = self::parseNature($request);
        if ($nature === false) {
            return ['message' => 'Invalid nature filter.'];
        }

        $type = self::parseType($request);
        if ($type === false) {
            return ['message' => 'Invalid type filter.'];
        }

        $competitionId = self::parseCompetitionId($request);
        if ($competitionId === false) {
            return ['message' => 'Invalid competition filter.'];
        }

        $includeRefused = filter_var($request->query->get('includeRefused', false), FILTER_VALIDATE_BOOLEAN);

        return new MatchHistoryFilters(
            nature: $nature,
            type: $type,
            range: $range,
            competitionId: $competitionId,
            includeRefused: $includeRefused,
        );
    }

    private static function parseNature(Request $request): MatchNature|null|false
    {
        $natureParam = $request->query->get('nature');
        if ($natureParam === null || $natureParam === '' || $natureParam === 'all') {
            return null;
        }

        return MatchNature::tryFrom((string) $natureParam) ?? false;
    }

    private static function parseType(Request $request): GameType|null|false
    {
        $typeParam = $request->query->get('type');
        if ($typeParam === null || $typeParam === '' || $typeParam === 'all') {
            return null;
        }

        return GameType::tryFrom((string) $typeParam) ?? false;
    }

    private static function parseCompetitionId(Request $request): int|null|false
    {
        $competitionParam = $request->query->get('competitionId');
        if ($competitionParam === null || $competitionParam === '' || $competitionParam === 'all') {
            return null;
        }

        if (!is_numeric($competitionParam) || (int) $competitionParam <= 0) {
            return false;
        }

        return (int) $competitionParam;
    }
}
