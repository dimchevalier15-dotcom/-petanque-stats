import type { JumpShotDistance, JumpShotThrowResult } from '../../models/JumpShot'

export interface JumpShotSessionHistoryItemResponseDto {
  id: number
  createdAt: string
  finishedAt: string
  seriesCount: number
  totalThrows: number
}

export interface JumpShotSessionHistoryResponseDto {
  page: number
  pageSize: number
  total: number
  items: JumpShotSessionHistoryItemResponseDto[]
}

export interface JumpShotStatsSummaryResponseDto {
  sessionsCount: number
  totalSeries: number
  totalThrows: number
  averageSeriesPerSession: number | null
  fewestSeriesToWin: number | null
  overallSuccessRate: number | null
  specialSeriesCount: number
}

export interface JumpShotStatsEvolutionPointResponseDto {
  sessionId: number
  date: string
  seriesCount: number
}

export interface JumpShotStatsDistanceResponseDto {
  distance: number
  throwCount: number
  successRate: number
}

export interface JumpShotStatsResultResponseDto {
  result: string
  count: number
}

export interface JumpShotStatsResponseDto {
  status: 'ok' | 'no_sessions' | 'no_data_in_period'
  summary: JumpShotStatsSummaryResponseDto
  evolution: JumpShotStatsEvolutionPointResponseDto[]
  byDistance: JumpShotStatsDistanceResponseDto[]
  byResult: JumpShotStatsResultResponseDto[]
}

export type { JumpShotDistance, JumpShotThrowResult }
