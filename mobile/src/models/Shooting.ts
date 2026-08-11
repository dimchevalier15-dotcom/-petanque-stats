/**
 * Official "tir de précision" business rules, centralized here so the
 * scoring barème is never scattered as magic numbers across components.
 * Mirrors the backend's App\Service\Shooting\ShootingScoreCalculator.
 */
export type ShootingWorkshop = 1 | 2 | 3 | 4 | 5
export type ShootingDistance = 6 | 7 | 8 | 9
export type ShootingShotResult = 'missed' | 'touched' | 'successful' | 'carreau'

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
  finishedAt: string | null
  totalScore: number | null
  workshops: ShootingWorkshopSummary[]
}

export interface ShootingSessionStarted {
  id: number
  createdAt: string
}

export interface ShootingSessionHistoryItem {
  id: number
  createdAt: string
  finishedAt: string
  totalScore: number
}

export interface ShootingSessionHistoryPage {
  page: number
  pageSize: number
  total: number
  items: ShootingSessionHistoryItem[]
}
