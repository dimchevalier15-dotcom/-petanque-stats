export const JUMP_SHOT_DISTANCES = [6, 7, 8, 9] as const
export type JumpShotDistance = (typeof JUMP_SHOT_DISTANCES)[number]

export const JUMP_SHOT_THROW_RESULTS = ['missed', 'chique', 'hit', 'carreau'] as const
export type JumpShotThrowResult = (typeof JUMP_SHOT_THROW_RESULTS)[number]

export const JUMP_SHOT_SERIES_CONSEQUENCES = ['down', 'stay', 'up'] as const
export type JumpShotSeriesConsequence = (typeof JUMP_SHOT_SERIES_CONSEQUENCES)[number]

export interface JumpShotSessionStarted {
  id: number
  createdAt: string
  currentDistance: JumpShotDistance
  seriesCount: number
}

export interface JumpShotThrowSummary {
  position: number
  result: JumpShotThrowResult
}

export interface JumpShotSeriesSummary {
  seriesNumber: number
  distance: JumpShotDistance
  successCount: number
  isSpecialSeries: boolean
  consequence: JumpShotSeriesConsequence
  createdAt: string
  throws: JumpShotThrowSummary[]
}

export interface JumpShotSessionSummary {
  id: number
  createdAt: string
  finishedAt: string | null
  won: boolean
  currentDistance: JumpShotDistance
  seriesCount: number
  series: JumpShotSeriesSummary[]
}

export interface SubmitJumpShotSeriesResult {
  series: JumpShotSeriesSummary
  nextDistance: JumpShotDistance
  sessionWon: boolean
  sessionFinished: boolean
  session: JumpShotSessionSummary
}

export interface RevertJumpShotLastSeriesResult {
  session: JumpShotSessionSummary
  restoredThrows: JumpShotThrowSummary[]
}

export interface JumpShotSessionHistoryItem {
  id: number
  createdAt: string
  finishedAt: string
  seriesCount: number
  totalThrows: number
}

export interface JumpShotSessionHistoryPage {
  page: number
  pageSize: number
  total: number
  items: JumpShotSessionHistoryItem[]
}

export type JumpShotStatsStatus = 'ok' | 'no_sessions' | 'no_data_in_period'

export interface JumpShotStatsSummary {
  sessionsCount: number
  totalSeries: number
  totalThrows: number
  averageSeriesPerSession: number | null
  fewestSeriesToWin: number | null
  overallSuccessRate: number | null
  specialSeriesCount: number
}

export interface JumpShotStatsEvolutionPoint {
  sessionId: number
  date: string
  seriesCount: number
}

export interface JumpShotStatsDistance {
  distance: JumpShotDistance
  throwCount: number
  successRate: number
}

export interface JumpShotStatsResultBreakdown {
  result: JumpShotThrowResult
  count: number
}

export interface JumpShotStats {
  status: JumpShotStatsStatus
  summary: JumpShotStatsSummary
  evolution: JumpShotStatsEvolutionPoint[]
  byDistance: JumpShotStatsDistance[]
  byResult: JumpShotStatsResultBreakdown[]
}

export function jumpShotResultIsSuccess(result: JumpShotThrowResult): boolean {
  return result !== 'missed'
}
