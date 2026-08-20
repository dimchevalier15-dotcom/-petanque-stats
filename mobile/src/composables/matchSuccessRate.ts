import type { MatchSummaryShotBreakdown } from '../models/MatchSummary'

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
