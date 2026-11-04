/**
 * "Dans les ronds" rules — 15 balls, each aimed freely at one of three 50 cm
 * rings. Pointing: a ball stopping inside the aimed ring scores that ring's
 * points. Shooting: hitting the ring's target ball scores them, doubled on a
 * carreau. Mirrors RingsMode / RingsTarget / RingsSessionService on the API.
 */
export type RingsMode = 'point' | 'shooting'
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

/** Shooting variant: gap between the jack and the target ball at the middle ring. */
export const RINGS_SHOOTING_JACK_GAP_CM = 15
/** Shooting variant: gap between the target ball and each neighbour ball at the far ring. */
export const RINGS_SHOOTING_SIDE_GAP_CM = 3
export const RINGS_CARREAU_MULTIPLIER = 2

export function ringsAllowsCarreau(mode: RingsMode): boolean {
  return mode === 'shooting'
}

export function ringsMaxSessionScore(mode: RingsMode): number {
  return RINGS_MAX_SESSION_SCORE * (ringsAllowsCarreau(mode) ? RINGS_CARREAU_MULTIPLIER : 1)
}

export function ringsThrowScore(target: RingsTarget, success: boolean, carreau = false): number {
  if (!success) return 0
  return RINGS_TARGET_POINTS[target] * (carreau ? RINGS_CARREAU_MULTIPLIER : 1)
}

export interface RingsThrow {
  target: RingsTarget
  success: boolean
  carreau?: boolean
}

export interface RingsTargetBreakdown {
  target: RingsTarget
  attempts: number
  successes: number
  carreaux: number
  points: number
}

export function ringsTargetBreakdown(throws: readonly RingsThrow[]): RingsTargetBreakdown[] {
  return RINGS_TARGETS.map((target) => {
    const aimed = throws.filter((t) => t.target === target)
    return {
      target,
      attempts: aimed.length,
      successes: aimed.filter((t) => t.success).length,
      carreaux: aimed.filter((t) => t.carreau === true).length,
      points: aimed.reduce((sum, t) => sum + ringsThrowScore(t.target, t.success, t.carreau), 0),
    }
  })
}

export interface RingsModeConfig {
  titleKey: string
  subtitleKey: string
  routes: { home: string; stats: string; rules: string; session: string; summary: string }
}

export const RINGS_MODE_CONFIG: Readonly<Record<RingsMode, RingsModeConfig>> = {
  point: {
    titleKey: 'rings.home.title',
    subtitleKey: 'rings.home.subtitle',
    routes: {
      home: 'ringsHome',
      stats: 'ringsStats',
      rules: 'ringsRules',
      session: 'ringsSession',
      summary: 'ringsSessionSummary',
    },
  },
  shooting: {
    titleKey: 'ringsShooting.home.title',
    subtitleKey: 'ringsShooting.home.subtitle',
    routes: {
      home: 'ringsShootingHome',
      stats: 'ringsShootingStats',
      rules: 'ringsShootingRules',
      session: 'ringsShootingSession',
      summary: 'ringsShootingSessionSummary',
    },
  },
}

export interface RingsThrowSummary extends RingsThrow {
  position: number
  carreau: boolean
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

export interface RingsStatsSummary {
  sessionsCount: number
  totalThrows: number
  averageSessionScore: number | null
  bestSessionScore: number | null
  overallSuccessRate: number | null
  maxSessionScore: number
  carreauCount: number
  carreauRate: number | null
}

export interface RingsStatsEvolutionPoint {
  sessionId: number
  date: string
  totalScore: number
}

export interface RingsStatsTargetRow {
  target: RingsTarget
  attemptCount: number
  successCount: number
  successRate: number
  carreauCount: number
  averagePointsPerAttempt: number
}

export interface RingsStatsScoreDecile {
  decile: number
  minScore: number
  maxScore: number
  sessionCount: number
}

export interface RingsStats {
  status: 'ok' | 'no_sessions' | 'no_data_in_period'
  summary: RingsStatsSummary
  evolution: RingsStatsEvolutionPoint[]
  byTarget: RingsStatsTargetRow[]
  scoreByDecile: RingsStatsScoreDecile[]
}
