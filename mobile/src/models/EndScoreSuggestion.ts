import type { EndRecord, TeamSide } from './MatchPlay'

export interface EndScoreSuggestion {
  winner: TeamSide | null
  points: number
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
): number {
  const currentScore = winner === 'A' ? scoreA : scoreB
  const remaining = targetScore - currentScore
  if (remaining <= 0) {
    return 1
  }
  return Math.min(13, remaining)
}

export function clampEndPoints(
  winner: TeamSide,
  points: number,
  scoreA: number,
  scoreB: number,
  targetScore: number,
): number {
  const maxPoints = maxPointsForWinner(winner, scoreA, scoreB, targetScore)
  const normalized = Number.isFinite(points) ? Math.trunc(points) : 1
  return Math.max(1, Math.min(normalized, maxPoints))
}

/**
 * Suggests a winner and points when the end score dialog opens.
 * Winner: team with the higher sum of ball note results.
 * Points: absolute difference between team sums, capped to reach target score.
 */
export function suggestEndScore(params: {
  end: EndRecord
  teamA: readonly number[]
  teamB: readonly number[]
  scoreA: number
  scoreB: number
  targetScore: number
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
    return { winner: null, points: defaultPoints }
  }

  return {
    winner,
    points: clampEndPoints(winner, defaultPoints, params.scoreA, params.scoreB, params.targetScore),
  }
}
