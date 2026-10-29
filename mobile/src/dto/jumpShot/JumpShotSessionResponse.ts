import type { JumpShotProgressionMode } from '../../models/JumpShot'

export interface JumpShotSessionStartedResponseDto {
  id: number
  createdAt: string
  currentDistance: number
  seriesCount: number
  progressionMode: string
}

export interface JumpShotThrowSummaryResponseDto {
  position: number
  result: string
}

export interface JumpShotSeriesSummaryResponseDto {
  seriesNumber: number
  distance: number
  successCount: number
  isSpecialSeries: boolean
  consequence: string
  createdAt: string
  throws: JumpShotThrowSummaryResponseDto[]
}

export interface JumpShotSessionSummaryResponseDto {
  id: number
  createdAt: string
  finishedAt: string | null
  won: boolean
  currentDistance: number
  seriesCount: number
  progressionMode: string
  series: JumpShotSeriesSummaryResponseDto[]
}

export interface SubmitJumpShotSeriesResponseDto {
  series: JumpShotSeriesSummaryResponseDto
  nextDistance: number
  sessionWon: boolean
  sessionFinished: boolean
  session: JumpShotSessionSummaryResponseDto
}

export interface RevertJumpShotLastSeriesResponseDto {
  session: JumpShotSessionSummaryResponseDto
  restoredThrows: JumpShotThrowSummaryResponseDto[]
}

export interface JumpShotThrowInputDto {
  position: number
  result: JumpShotThrowResult
}

export interface SubmitJumpShotSeriesRequestDto {
  throws: JumpShotThrowInputDto[]
}

export interface StartJumpShotSessionRequestDto {
  progressionMode?: JumpShotProgressionMode
}

export type { JumpShotSeriesConsequence, JumpShotThrowResult }
