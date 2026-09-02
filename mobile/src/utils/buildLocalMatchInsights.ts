import type { MatchType } from '../models/Match'
import type { EndRecord, EndShot, TeamSide } from '../models/MatchPlay'
import type {
  MatchInsights,
  MatchInsightsByDistance,
  MatchInsightsDistanceOutlook,
  MatchInsightsHeldEndError,
  MatchInsightsMarkingRate,
  MatchInsightsMarkingTeam,
  MatchInsightsRajoutTeam,
  MatchInsightsTeam,
} from '../models/MatchInsights'
import { successRateFromNoteCounts } from '../composables/matchSuccessRate'
import { hasValidShotSequence } from './matchEndShots'
import { allMatchPlayersTracked, ballsPerPlayerForType, playersPerTeamForType } from './matchAllTracked'
import { teamForPlayer } from './matchRoles'

const DOMINANCE_AVG_GAP = 0.5
const DOMINANCE_MIN_BALLS = 3
const LIMITED_DAMAGE_MAX_POINTS = 2

const DISTANCE_BUCKETS = ['under_6', '6_7', '7_8', '8_9', '9_10', '10_plus'] as const

function distanceBucket(distance: number): (typeof DISTANCE_BUCKETS)[number] | null {
  if (distance < 6) return 'under_6'
  if (distance < 7) return '6_7'
  if (distance < 8) return '7_8'
  if (distance < 9) return '8_9'
  if (distance < 10) return '9_10'
  return '10_plus'
}

interface TeamStats {
  endsWon: number
  endsOpened: number
  endsWonWhenOpened: number
  firstShotSum: number
  firstShotCount: number
  capitalizedCount: number
  capitalizationOpportunities: number
  capitalizedPointsSum: number
  defendedCount: number
  defenseSituations: number
  defensePointsSum: number
  reclaimsCount: number
}

interface MarkingCounters {
  made: number
  attempts: number
}

interface HeldEndErrorCounters {
  minusTwoCount: number
  ballsPlayed: number
}

interface DistanceAgg {
  sum: number
  count: number
  pointP2: number
  pointP1: number
}

function emptyTeamStats(): TeamStats {
  return {
    endsWon: 0,
    endsOpened: 0,
    endsWonWhenOpened: 0,
    firstShotSum: 0,
    firstShotCount: 0,
    capitalizedCount: 0,
    capitalizationOpportunities: 0,
    capitalizedPointsSum: 0,
    defendedCount: 0,
    defenseSituations: 0,
    defensePointsSum: 0,
    reclaimsCount: 0,
  }
}

function emptyMarkingCounters(): MarkingCounters {
  return { made: 0, attempts: 0 }
}

function emptyMarkingStats(): Record<TeamSide, Record<'point' | 'tir', MarkingCounters>> {
  return {
    A: { point: emptyMarkingCounters(), tir: emptyMarkingCounters() },
    B: { point: emptyMarkingCounters(), tir: emptyMarkingCounters() },
  }
}

function emptyHeldEndErrorStats(): Record<TeamSide, HeldEndErrorCounters> {
  return {
    A: { minusTwoCount: 0, ballsPlayed: 0 },
    B: { minusTwoCount: 0, ballsPlayed: 0 },
  }
}

function emptyDistanceAgg(): DistanceAgg {
  return { sum: 0, count: 0, pointP2: 0, pointP1: 0 }
}

function analyzeClosing(
  teamWithBalls: TeamSide,
  teamOut: TeamSide,
  remaining: Record<TeamSide, number>,
  winner: TeamSide,
  points: number,
  stats: Record<TeamSide, TeamStats>,
): void {
  if (remaining[teamWithBalls] <= 0 || remaining[teamOut] > 0) {
    return
  }

  stats[teamWithBalls].capitalizationOpportunities++
  if (winner === teamWithBalls) {
    stats[teamWithBalls].capitalizedCount++
    stats[teamWithBalls].capitalizedPointsSum += points
  }

  stats[teamOut].defenseSituations++
  if (winner === teamOut || points <= LIMITED_DAMAGE_MAX_POINTS) {
    stats[teamOut].defendedCount++
    stats[teamOut].defensePointsSum += points
  }
}

function analyzeEnd(
  end: EndRecord,
  shots: EndShot[],
  teamOfPlayer: (playerId: number) => TeamSide | null,
  teamCapacities: Record<TeamSide, number>,
  teamStats: Record<TeamSide, TeamStats>,
  markingStats: Record<TeamSide, Record<'point' | 'tir', MarkingCounters>>,
  rajoutStats: Record<TeamSide, Record<'point' | 'tir', MarkingCounters>>,
  heldEndErrorStats: Record<TeamSide, HeldEndErrorCounters>,
  distanceAgg: Record<string, Record<TeamSide, DistanceAgg>>,
  counters: { totalBalls: number; ballsWithDistance: number },
): void {
  const firstTeam = teamOfPlayer(shots[0]!.playerId)
  if (firstTeam) {
    teamStats[firstTeam].endsOpened++
    teamStats[firstTeam].firstShotSum += shots[0]!.note
    teamStats[firstTeam].firstShotCount++
  }

  const winner = end.winner ?? 'A'
  if (winner === 'A' || winner === 'B') {
    teamStats[winner].endsWon++
    if (firstTeam && winner === firstTeam) {
      teamStats[firstTeam].endsWonWhenOpened++
    }
  }

  let pointHolder: TeamSide | null = firstTeam
  const playedByTeam: Record<TeamSide, number> = { A: 0, B: 0 }
  let markingTeam: TeamSide | null = null
  let markingActive = false
  let rajoutActive = false
  let rajoutTeam: TeamSide | null = null

  for (const shot of shots) {
    const team = teamOfPlayer(shot.playerId)
    if (!team) continue

    const opponent: TeamSide = team === 'A' ? 'B' : 'A'
    let markSuccess = false

    if (
      markingActive &&
      team === markingTeam &&
      !shot.isCochonnet &&
      (shot.shotType === 'point' || shot.shotType === 'tir')
    ) {
      markingStats[team][shot.shotType].attempts++
      if (shot.note >= 1) {
        markingStats[team][shot.shotType].made++
        markingActive = false
        markSuccess = true
      }
    }

    if (
      rajoutActive &&
      team === rajoutTeam &&
      !shot.isCochonnet &&
      (shot.shotType === 'point' || shot.shotType === 'tir')
    ) {
      rajoutStats[team][shot.shotType].attempts++
      if (shot.note >= 1) {
        rajoutStats[team][shot.shotType].made++
      }
      if (shot.note === -2) {
        rajoutActive = false
      }
    }

    const opponentRemainingBefore = teamCapacities[opponent] - playedByTeam[opponent]
    if (
      opponentRemainingBefore <= 0 &&
      !shot.isCochonnet &&
      (shot.shotType === 'point' || shot.shotType === 'tir')
    ) {
      heldEndErrorStats[team].ballsPlayed++
      if (shot.note === -2) {
        heldEndErrorStats[team].minusTwoCount++
      }
    }

    if (shot.shotType === 'tir' && !shot.isCochonnet && shot.note >= 1) {
      teamStats[team].reclaimsCount++
      pointHolder = team
    } else if (shot.shotType === 'point' && shot.note >= 1 && pointHolder !== team) {
      pointHolder = team
    }

    playedByTeam[team]++
    counters.totalBalls++

    const teamRemaining = teamCapacities[team] - playedByTeam[team]
    const opponentRemaining = teamCapacities[opponent] - playedByTeam[opponent]

    if (markSuccess && teamRemaining > 0) {
      rajoutActive = true
      rajoutTeam = markingTeam
    }

    if (markingTeam === null && !rajoutActive && teamRemaining <= 0 && opponentRemaining > 0) {
      if (shot.note <= 0) {
        rajoutActive = true
        rajoutTeam = opponent
      } else {
        markingTeam = opponent
        markingActive = true
      }
    }

    if (shot.distance !== null && shot.distance !== undefined) {
      counters.ballsWithDistance++
      const bucket = distanceBucket(shot.distance)
      if (bucket) {
        if (!distanceAgg[bucket]) {
          distanceAgg[bucket] = { A: emptyDistanceAgg(), B: emptyDistanceAgg() }
        }
        const agg = distanceAgg[bucket]![team]
        agg.sum += shot.note
        agg.count++
        if (shot.shotType === 'point' && !shot.isCochonnet) {
          if (shot.note === 2) agg.pointP2++
          else if (shot.note === 1) agg.pointP1++
        }
      }
    }
  }

  const remaining: Record<TeamSide, number> = {
    A: teamCapacities.A - playedByTeam.A,
    B: teamCapacities.B - playedByTeam.B,
  }

  analyzeClosing('A', 'B', remaining, winner, end.points ?? 0, teamStats)
  analyzeClosing('B', 'A', remaining, winner, end.points ?? 0, teamStats)
}

function toMarkingRate(counters: MarkingCounters): MatchInsightsMarkingRate {
  return {
    made: counters.made,
    attempts: counters.attempts,
    rate: counters.attempts > 0 ? Math.round((counters.made / counters.attempts) * 1000) / 10 : null,
  }
}

function toMarkingTeam(stats: Record<'point' | 'tir', MarkingCounters>): MatchInsightsMarkingTeam {
  return {
    point: toMarkingRate(stats.point),
    tir: toMarkingRate(stats.tir),
  }
}

function toRajoutTeam(stats: Record<'point' | 'tir', MarkingCounters>): MatchInsightsRajoutTeam {
  return toMarkingTeam(stats)
}

function buildByDistance(distanceAgg: Record<string, Record<TeamSide, DistanceAgg>>): MatchInsightsByDistance[] {
  const rows: MatchInsightsByDistance[] = []

  for (const bucket of DISTANCE_BUCKETS) {
    const agg = distanceAgg[bucket]
    if (!agg) continue

    const teamA =
      agg.A.count > 0
        ? {
            average: Math.round((agg.A.sum / agg.A.count) * 100) / 100,
            balls: agg.A.count,
            pointSuccessRate: successRateFromNoteCounts(agg.A.pointP2, agg.A.pointP1, 0, 0, 0),
          }
        : null
    const teamB =
      agg.B.count > 0
        ? {
            average: Math.round((agg.B.sum / agg.B.count) * 100) / 100,
            balls: agg.B.count,
            pointSuccessRate: successRateFromNoteCounts(agg.B.pointP2, agg.B.pointP1, 0, 0, 0),
          }
        : null

    if (!teamA && !teamB) continue

    let dominantTeam: TeamSide | null = null
    if (teamA && teamB && teamA.balls >= DOMINANCE_MIN_BALLS && teamB.balls >= DOMINANCE_MIN_BALLS) {
      const gap = teamA.average - teamB.average
      if (gap >= DOMINANCE_AVG_GAP) dominantTeam = 'A'
      else if (gap <= -DOMINANCE_AVG_GAP) dominantTeam = 'B'
    }

    rows.push({ bucket, teamA, teamB, dominantTeam })
  }

  return rows
}

function buildDistanceOutlook(distanceAgg: Record<string, Record<TeamSide, DistanceAgg>>): MatchInsightsDistanceOutlook {
  const byDistance = buildByDistance(distanceAgg)
  const dominantRows = byDistance.filter((row) => row.dominantTeam !== null)

  if (dominantRows.length === 0) {
    return { singleDominantTeam: null, competitiveBuckets: [] }
  }

  const teams = [...new Set(dominantRows.map((row) => row.dominantTeam).filter(Boolean))] as TeamSide[]
  if (teams.length === 1) {
    return { singleDominantTeam: teams[0]!, competitiveBuckets: [] }
  }

  return { singleDominantTeam: null, competitiveBuckets: dominantRows }
}

function toHeldEndErrorResponse(counters: HeldEndErrorCounters): MatchInsightsHeldEndError {
  return {
    minusTwoCount: counters.minusTwoCount,
    ballsPlayed: counters.ballsPlayed,
    rate:
      counters.ballsPlayed > 0
        ? Math.round((counters.minusTwoCount / counters.ballsPlayed) * 1000) / 10
        : null,
  }
}

function toTeamResponse(team: TeamSide, stats: TeamStats): MatchInsightsTeam {
  return {
    team,
    endsWon: stats.endsWon,
    endsOpened: stats.endsOpened,
    firstShotAverage:
      stats.firstShotCount > 0 ? Math.round((stats.firstShotSum / stats.firstShotCount) * 100) / 100 : 0,
    capitalizedCount: stats.capitalizedCount,
    capitalizationOpportunities: stats.capitalizationOpportunities,
    avgPointsWhenCapitalizing:
      stats.capitalizedCount > 0
        ? Math.round((stats.capitalizedPointsSum / stats.capitalizedCount) * 100) / 100
        : 0,
    defendedCount: stats.defendedCount,
    defenseSituations: stats.defenseSituations,
    avgPointsConcededWhenDefending:
      stats.defendedCount > 0 ? Math.round((stats.defensePointsSum / stats.defendedCount) * 100) / 100 : 0,
    reclaimsCount: stats.reclaimsCount,
  }
}

export function buildLocalMatchInsights(params: {
  type: MatchType
  teamA: number[]
  teamB: number[]
  trackedPlayers: number[]
  ends: EndRecord[]
}): MatchInsights {
  if (
    !allMatchPlayersTracked({
      teamA: params.teamA,
      teamB: params.teamB,
      trackedPlayers: params.trackedPlayers,
    })
  ) {
    return { status: 'unavailable', reason: 'not_all_tracked' }
  }

  const teamOfPlayer = (playerId: number) => teamForPlayer(playerId, params.teamA, params.teamB)
  const ballsPerPlayer = ballsPerPlayerForType(params.type)
  const teamCapacities: Record<TeamSide, number> = {
    A: playersPerTeamForType(params.type) * ballsPerPlayer,
    B: playersPerTeamForType(params.type) * ballsPerPlayer,
  }

  const teamStats: Record<TeamSide, TeamStats> = { A: emptyTeamStats(), B: emptyTeamStats() }
  const markingStats = emptyMarkingStats()
  const rajoutStats = emptyMarkingStats()
  const heldEndErrorStats = emptyHeldEndErrorStats()
  const distanceAgg: Record<string, Record<TeamSide, DistanceAgg>> = {}
  const counters = { totalBalls: 0, ballsWithDistance: 0 }
  let endsAnalyzed = 0

  for (const end of params.ends) {
    if (end.canceled) continue
    const shots = [...end.shots].sort((a, b) => a.sequenceOrder - b.sequenceOrder)
    if (shots.length === 0) continue

    if (!hasValidShotSequence(end, ballsPerPlayer)) {
      return { status: 'unavailable', reason: 'invalid_sequence' }
    }

    endsAnalyzed++
    analyzeEnd(
      end,
      shots,
      teamOfPlayer,
      teamCapacities,
      teamStats,
      markingStats,
      rajoutStats,
      heldEndErrorStats,
      distanceAgg,
      counters,
    )
  }

  if (endsAnalyzed === 0) {
    return { status: 'unavailable', reason: 'no_data' }
  }

  return {
    status: 'ok',
    teamA: toTeamResponse('A', teamStats.A),
    teamB: toTeamResponse('B', teamStats.B),
    markingTeamA: toMarkingTeam(markingStats.A),
    markingTeamB: toMarkingTeam(markingStats.B),
    rajoutTeamA: toRajoutTeam(rajoutStats.A),
    rajoutTeamB: toRajoutTeam(rajoutStats.B),
    heldEndErrorTeamA: toHeldEndErrorResponse(heldEndErrorStats.A),
    heldEndErrorTeamB: toHeldEndErrorResponse(heldEndErrorStats.B),
    pointDominanceTeamA: {
      endsWonWhenOpened: teamStats.A.endsWonWhenOpened,
      endsOpened: teamStats.A.endsOpened,
    },
    pointDominanceTeamB: {
      endsWonWhenOpened: teamStats.B.endsWonWhenOpened,
      endsOpened: teamStats.B.endsOpened,
    },
    distanceOutlook: buildDistanceOutlook(distanceAgg),
    coverage: {
      distanceSampleRate: counters.totalBalls > 0 ? Math.round((counters.ballsWithDistance / counters.totalBalls) * 100) / 100 : 0,
      endsAnalyzed,
    },
  }
}
