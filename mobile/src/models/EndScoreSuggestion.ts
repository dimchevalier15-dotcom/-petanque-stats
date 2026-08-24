import type { MatchType } from './Match'
import type { EndRecord, TeamSide } from './MatchPlay'

export interface EndScoreSuggestion {
  winner: TeamSide | null
  points: number
}

/** Maximum points that can be scored in a single end, by match format. */
export function maxPointsPerEnd(type: MatchType): number {
  return type === 'tete_a_tete' ? 3 : 6
}

export function sumTeamBallResults(end: EndRecord, teamPlayerIds: readonly number[]): number {
  let total = 0
  for (const playerId of teamPlayerIds) {
    const entry = end.balls.find((ball) => ball.playerId === playerId)
    if (entry) {
      total += entry.notes.reduce((acc, note) => acc + note, 0)
    }
  }
  return total
}

export function maxPointsForWinner(
  winner: TeamSide,
  scoreA: number,
  scoreB: number,
  targetScore: number,
  type: MatchType,
): number {
  const currentScore = winner === 'A' ? scoreA : scoreB
  const remaining = targetScore - currentScore
  if (remaining <= 0) {
    return 1
  }
  return Math.min(maxPointsPerEnd(type), remaining)
}

export function clampEndPoints(
  winner: TeamSide,
  points: number,
  scoreA: number,
  scoreB: number,
  targetScore: number,
  type: MatchType,
  minPoints = 1,
): number {
  const maxPoints = maxPointsForWinner(winner, scoreA, scoreB, targetScore, type)
  const floor = minPoints > 0 ? minPoints : 0
  const normalized = Number.isFinite(points) ? Math.trunc(points) : floor
  return Math.max(floor, Math.min(normalized, maxPoints))
}

/**
 * Suggests a winner and points when the end score dialog opens.
 * Winner: team with the higher sum of ball note results.
 * Points: absolute difference between team sums, capped to the format max and remaining score.
 */
export function suggestEndScore(params: {
  end: EndRecord
  teamA: readonly number[]
  teamB: readonly number[]
  scoreA: number
  scoreB: number
  targetScore: number
  type: MatchType
}): EndScoreSuggestion {
  const resultA = sumTeamBallResults(params.end, params.teamA)
  const resultB = sumTeamBallResults(params.end, params.teamB)

  let winner: TeamSide | null = null
  if (resultA > resultB) {
    winner = 'A'
  } else if (resultB > resultA) {
    winner = 'B'
  }

  const rawPoints = Math.abs(resultA - resultB)
  const defaultPoints = rawPoints > 0 ? rawPoints : 1

  if (winner === null) {
    return { winner: null, points: Math.min(defaultPoints, maxPointsPerEnd(params.type)) }
  }

  return {
    winner,
    points: clampEndPoints(
      winner,
      defaultPoints,
      params.scoreA,
      params.scoreB,
      params.targetScore,
      params.type,
    ),
  }
}
