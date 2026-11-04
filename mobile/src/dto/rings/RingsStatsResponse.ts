import type { RingsTarget } from '../../models/Rings'

export interface RingsStatsSummaryDto {
  sessionsCount: number
  totalThrows: number
  averageSessionScore: number | null
  bestSessionScore: number | null
  overallSuccessRate: number | null
  maxSessionScore: number
  carreauCount: number
  carreauRate: number | null
}

export interface RingsStatsEvolutionPointDto {
  sessionId: number
  date: string
  totalScore: number
}

export interface RingsStatsTargetDto {
  target: RingsTarget
  attemptCount: number
  successCount: number
  successRate: number
  carreauCount: number
  averagePointsPerAttempt: number
}

export interface RingsStatsScoreDecileDto {
  decile: number
  minScore: number
  maxScore: number
  sessionCount: number
}

export interface RingsStatsResponseDto {
  status: 'ok' | 'no_sessions' | 'no_data_in_period'
  summary: RingsStatsSummaryDto
  evolution: RingsStatsEvolutionPointDto[]
  byTarget: RingsStatsTargetDto[]
  scoreByDecile: RingsStatsScoreDecileDto[]
}
