/**
 * "Point dans les ronds" rules — 15 balls, each aimed freely at one of three
 * 50 cm rings. A ball stopping inside the aimed ring scores that ring's points.
 * Mirrors RingsTarget / RingsSessionService on the API.
 */
export type RingsMode = 'point'
export type RingsTarget = 'near' | 'middle' | 'far'

/** Farthest ring first, matching the field layout seen from the throwing circle. */
export const RINGS_TARGETS: readonly RingsTarget[] = ['far', 'middle', 'near']
export const RINGS_THROWS_PER_SESSION = 15
export const RINGS_RING_DIAMETER_CM = 50

export const RINGS_TARGET_DISTANCE_M: Readonly<Record<RingsTarget, number>> = {
  near: 6.5,
  middle: 7.5,
  far: 8.5,
}

export const RINGS_TARGET_POINTS: Readonly<Record<RingsTarget, number>> = {
  near: 1,
  middle: 3,
  far: 5,
}

/** Balls laid in front of each ring as obstacles; touching them is not a fault. */
export const RINGS_TARGET_OBSTACLES: Readonly<Record<RingsTarget, number>> = {
  near: 0,
  middle: 1,
  far: 2,
}

export const RINGS_MAX_SESSION_SCORE = RINGS_THROWS_PER_SESSION * RINGS_TARGET_POINTS.far

export function ringsThrowScore(target: RingsTarget, success: boolean): number {
  return success ? RINGS_TARGET_POINTS[target] : 0
}

export interface RingsThrow {
  target: RingsTarget
  success: boolean
}

export interface RingsTargetBreakdown {
  target: RingsTarget
  attempts: number
  successes: number
  points: number
}

export function ringsTargetBreakdown(throws: readonly RingsThrow[]): RingsTargetBreakdown[] {
  return RINGS_TARGETS.map((target) => {
    const aimed = throws.filter((t) => t.target === target)
    const successes = aimed.filter((t) => t.success).length
    return { target, attempts: aimed.length, successes, points: successes * RINGS_TARGET_POINTS[target] }
  })
}

export interface RingsThrowSummary extends RingsThrow {
  position: number
  score: number
}

export interface RingsSessionStarted {
  id: number
  mode: RingsMode
  createdAt: string
}

export interface RingsSessionSummary {
  id: number
  mode: RingsMode
  createdAt: string
  playedAt: string
  finishedAt: string | null
  totalScore: number | null
  throws: RingsThrowSummary[]
}

export interface RingsSessionHistoryItem {
  id: number
  mode: RingsMode
  playedAt: string
  finishedAt: string
  totalScore: number
}

export interface RingsSessionHistoryPage {
  page: number
  pageSize: number
  total: number
  items: RingsSessionHistoryItem[]
}
