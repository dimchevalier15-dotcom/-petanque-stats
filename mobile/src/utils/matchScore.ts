import type { EndRecord } from '../models/MatchPlay'

export interface MatchScore {
  scoreA: number
  scoreB: number
}

/** Points scored in recorded ends only. */
export function scoreFromEnds(ends: EndRecord[]): MatchScore {
  let scoreA = 0
  let scoreB = 0
  for (const end of ends) {
    if (end.canceled) continue
    if (end.winner && end.points) {
      if (end.winner === 'A') scoreA += end.points
      else scoreB += end.points
    }
  }
  return { scoreA, scoreB }
}

export function matchScore(
  ends: EndRecord[],
  openingScoreA = 0,
  openingScoreB = 0,
): MatchScore {
  const played = scoreFromEnds(ends)
  return {
    scoreA: openingScoreA + played.scoreA,
    scoreB: openingScoreB + played.scoreB,
  }
}

export function normalizeOpeningScore(value: number, targetScore: number): number {
  if (!Number.isFinite(value)) return 0
  const rounded = Math.trunc(value)
  if (rounded < 0) return 0
  if (rounded > targetScore) return targetScore
  return rounded
}

/** Opening scores so that recorded ends + opening = target totals. */
export function openingScoresForTarget(
  ends: EndRecord[],
  targetA: number,
  targetB: number,
  targetScore: number,
): MatchScore | null {
  const played = scoreFromEnds(ends)
  const totalA = normalizeOpeningScore(targetA, targetScore)
  const totalB = normalizeOpeningScore(targetB, targetScore)
  if (totalA < played.scoreA || totalB < played.scoreB) {
    return null
  }
  return { scoreA: totalA - played.scoreA, scoreB: totalB - played.scoreB }
}
