import type { MatchSummaryShotBreakdown } from '../models/MatchSummary'
import type { EndRecord } from '../models/MatchPlay'

export interface MastersScore {
  success: number
  total: number
}

export function successRateFromNoteCounts(
  p2: number,
  p1: number,
  p0: number,
  m1: number,
  m2: number,
): number | null {
  const total = p2 + p1 + p0 + m1 + m2
  if (total === 0) {
    return null
  }

  return Math.round(((p2 + p1) / total) * 1000) / 10
}

export function mastersFromNoteCounts(
  p2: number,
  p1: number,
  p0: number,
  m1: number,
  m2: number,
): MastersScore | null {
  const total = p2 + p1 + p0 + m1 + m2
  if (total === 0) {
    return null
  }

  return { success: p2 + p1, total }
}

export function shotSuccessRate(
  breakdown: MatchSummaryShotBreakdown | null | undefined,
): number | null {
  if (!breakdown) {
    return null
  }
  if (breakdown.successRate !== null && breakdown.successRate !== undefined) {
    return breakdown.successRate
  }

  return successRateFromNoteCounts(
    breakdown.p2,
    breakdown.p1,
    breakdown.p0,
    breakdown.m1,
    breakdown.m2,
  )
}

export function shotMasters(
  breakdown: MatchSummaryShotBreakdown | null | undefined,
): MastersScore | null {
  if (!breakdown) {
    return null
  }

  return mastersFromNoteCounts(
    breakdown.p2,
    breakdown.p1,
    breakdown.p0,
    breakdown.m1,
    breakdown.m2,
  )
}

export function formatMasters(score: MastersScore): string {
  return `${score.success}/${score.total}`
}

export function playerMastersFromEnds(ends: EndRecord[], playerId: number): MastersScore | null {
  let success = 0
  let total = 0

  for (const end of ends) {
    const entry = end.balls.find((ball) => ball.playerId === playerId)
    if (!entry) {
      continue
    }
    for (const note of entry.notes) {
      total++
      if (note >= 1) {
        success++
      }
    }
  }

  return total > 0 ? { success, total } : null
}

export function playerShotMastersFromEnds(
  ends: EndRecord[],
  playerId: number,
  shotType: 'point' | 'tir',
): MastersScore | null {
  let success = 0
  let total = 0

  for (const end of ends) {
    const entry = end.balls.find((ball) => ball.playerId === playerId)
    if (!entry) {
      continue
    }
    for (let i = 0; i < entry.notes.length; i++) {
      if (entry.shotTypes[i] !== shotType) {
        continue
      }
      total++
      if (entry.notes[i] >= 1) {
        success++
      }
    }
  }

  return total > 0 ? { success, total } : null
}
