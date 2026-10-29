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

export function jumpShotResultIsSuccess(result: JumpShotThrowResult): boolean {
  return result !== 'missed'
}
