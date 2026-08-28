/**
 * Official "tir de précision" business rules, centralized here so the
 * scoring barème is never scattered as magic numbers across components.
 * Mirrors the backend's App\Service\Shooting\ShootingScoreCalculator.
 */
export type ShootingWorkshop = 1 | 2 | 3 | 4 | 5
export type ShootingDistance = 6 | 7 | 8 | 9
export type ShootingShotResult = 'missed' | 'touched' | 'successful' | 'carreau'
export type ShootingContextNature = 'training' | 'competition'

export const SHOOTING_WORKSHOPS: readonly ShootingWorkshop[] = [1, 2, 3, 4, 5]
export const SHOOTING_DISTANCES: readonly ShootingDistance[] = [6, 7, 8, 9]

/** Workshop 5 (the jack) has no "carreau" category, per the official rules. */
export function isResultAllowedForWorkshop(workshop: ShootingWorkshop, result: ShootingShotResult): boolean {
  return workshop === 5 ? result !== 'carreau' : true
}

/** Workshop 5 (the jack) also has its own point scale (0 / 3 / 5, no carreau). */
export function pointsFor(workshop: ShootingWorkshop, result: ShootingShotResult): number {
  if (workshop === 5) {
    return { missed: 0, touched: 3, successful: 5, carreau: 0 }[result]
  }
  return { missed: 0, touched: 1, successful: 3, carreau: 5 }[result]
}

export function resultsForWorkshop(workshop: ShootingWorkshop): ShootingShotResult[] {
  const all: ShootingShotResult[] = ['missed', 'touched', 'successful', 'carreau']
  return all.filter((result) => isResultAllowedForWorkshop(workshop, result))
}

export interface ShootingShot {
  workshop: ShootingWorkshop
  distance: ShootingDistance
  result: ShootingShotResult
}

export interface ShootingShotSummary {
  distance: number
  result: ShootingShotResult
  score: number
}

export interface ShootingWorkshopSummary {
  workshop: number
  totalScore: number
  shots: ShootingShotSummary[]
}

export interface ShootingSessionSummary {
  id: number
  createdAt: string
  playedAt: string
  finishedAt: string | null
  totalScore: number | null
  contextNature: ShootingContextNature | null
  title: string | null
  description: string | null
  workshops: ShootingWorkshopSummary[]
}

export interface ShootingSessionStarted {
  id: number
  createdAt: string
}

export interface ShootingSessionHistoryItem {
  id: number
  createdAt: string
  playedAt: string
  finishedAt: string
  totalScore: number
  contextNature: ShootingContextNature | null
  title: string | null
}

export interface ShootingSessionContextForm {
  contextNature: ShootingContextNature
  playedAt: string
  title: string
  description: string
}

export interface ShootingSessionHistoryPage {
  page: number
  pageSize: number
  total: number
  items: ShootingSessionHistoryItem[]
}

export interface ShootingStatsSummary {
  sessionsCount: number
  totalShots: number
  averageSessionScore: number | null
  bestSessionScore: number | null
}

export interface ShootingStatsEvolutionPoint {
  sessionId: number
  date: string
  totalScore: number
}

export interface ShootingStatsWorkshop {
  workshop: number
  shotCount: number
  averageScore: number
}

export interface ShootingStatsDistance {
  distance: number
  shotCount: number
  averageScore: number
}

export interface ShootingStatsResult {
  result: ShootingShotResult
  count: number
}

export interface ShootingStatsCell {
  workshop: number
  distance: number
  shotCount: number
  averageScore: number
}

export interface ShootingStats {
  status: 'ok' | 'no_sessions' | 'no_data_in_period'
  summary: ShootingStatsSummary
  evolution: ShootingStatsEvolutionPoint[]
  byWorkshop: ShootingStatsWorkshop[]
  byDistance: ShootingStatsDistance[]
  byResult: ShootingStatsResult[]
  heatmap: ShootingStatsCell[]
}
