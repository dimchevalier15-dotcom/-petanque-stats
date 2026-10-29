/**
 * Official "point de précision" rules — same grid as tir (5 workshops x 4 distances),
 * scoring tiers 0 / 3 / 9 / 15 (max 300). Mirrors PointingScoreCalculator on the API.
 */
export type PointingWorkshop = 1 | 2 | 3 | 4 | 5
export type PointingDistance = 6 | 7 | 8 | 9
export type PointingShotResult = 'missed' | 'p3' | 'p9' | 'p15'

export const POINTING_WORKSHOPS: readonly PointingWorkshop[] = [1, 2, 3, 4, 5]
export const POINTING_DISTANCES: readonly PointingDistance[] = [6, 7, 8, 9]
export const POINTING_MAX_SESSION_SCORE = 300

export function pointsFor(_workshop: PointingWorkshop, result: PointingShotResult): number {
  return { missed: 0, p3: 3, p9: 9, p15: 15 }[result]
}

export function resultsForWorkshop(_workshop: PointingWorkshop): PointingShotResult[] {
  return ['missed', 'p3', 'p9', 'p15']
}

export interface PointingShot {
  workshop: PointingWorkshop
  distance: PointingDistance
  result: PointingShotResult
}

export interface PointingShotSummary {
  distance: number
  result: PointingShotResult
  score: number
}

export interface PointingWorkshopSummary {
  workshop: number
  totalScore: number
  shots: PointingShotSummary[]
}

export interface PointingSessionSummary {
  id: number
  createdAt: string
  playedAt: string
  finishedAt: string | null
  totalScore: number | null
  contextNature: string | null
  title: string | null
  description: string | null
  workshops: PointingWorkshopSummary[]
}

export interface PointingSessionStarted {
  id: number
  createdAt: string
}

export function pointingWorkshopI18nKey(workshop: number): string {
  const keys = ['jackAlone', 'ballInFrontOfJack', 'twoBallsInFrontOfJack', 'pushBall', 'pushJack']
  return keys[workshop - 1] ?? 'jackAlone'
}

export function pointingResultI18nKey(workshop: PointingWorkshop, result: PointingShotResult): string {
  return `pointing.results.w${workshop}.${result}`
}

export function pointingHeatmapCellColor(score: number): string {
  const ratio = Math.min(1, Math.max(0, score / 15))
  const r = Math.round(239 - ratio * (239 - 34))
  const g = Math.round(68 + ratio * (197 - 68))
  const b = Math.round(68 + ratio * (94 - 68))
  return `rgb(${r}, ${g}, ${b})`
}

export function pointingShotScoreSeverity(score: number): 'secondary' | 'danger' | 'warn' | 'success' | 'help' {
  if (score >= 15) return 'help'
  if (score >= 9) return 'success'
  if (score >= 3) return 'warn'
  return 'danger'
}

export type PointingContextNature = 'training' | 'competition'

export interface PointingSessionHistoryItem {
  id: number
  createdAt: string
  playedAt: string
  finishedAt: string
  totalScore: number
  contextNature: PointingContextNature | null
  title: string | null
}

export interface PointingSessionHistoryPage {
  page: number
  pageSize: number
  total: number
  items: PointingSessionHistoryItem[]
}

export interface PointingSessionContextForm {
  contextNature: PointingContextNature
  playedAt: string
  title: string
  description: string
}

export interface PointingStatsSummary {
  sessionsCount: number
  totalShots: number
  averageSessionScore: number | null
  bestSessionScore: number | null
}

export interface PointingStatsEvolutionPoint {
  sessionId: number
  date: string
  totalScore: number
}

export interface PointingStatsWorkshop {
  workshop: number
  shotCount: number
  averageScore: number
}

export interface PointingStatsDistance {
  distance: number
  shotCount: number
  averageScore: number
}

export interface PointingStatsResult {
  result: PointingShotResult
  count: number
}

export interface PointingStatsCell {
  workshop: number
  distance: number
  shotCount: number
  averageScore: number
}

export interface PointingStats {
  status: 'ok' | 'no_sessions' | 'no_data_in_period'
  summary: PointingStatsSummary
  evolution: PointingStatsEvolutionPoint[]
  byWorkshop: PointingStatsWorkshop[]
  byDistance: PointingStatsDistance[]
  byResult: PointingStatsResult[]
  heatmap: PointingStatsCell[]
}
