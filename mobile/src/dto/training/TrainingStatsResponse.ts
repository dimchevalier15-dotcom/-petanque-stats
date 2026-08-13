import type { TrainingType } from '../../models/Training'

export interface TrainingStatsSummaryDto {
  sessionsCount: number
  totalBalls: number
  successfulBalls: number
  successRate: number | null
  bestScore: number | null
  averageScore: number | null
}

export interface TrainingStatsEvolutionPointDto {
  sessionId: number
  date: string
  totalScore: number
  plannedBalls: number
  successRate: number
}

export interface TrainingStatsTypeDto {
  type: TrainingType
  ballCount: number
  successRate: number
  averageScore: number
}

export interface TrainingStatsDistanceDto {
  distance: number
  ballCount: number
  successRate: number
  averageScore: number
}

export interface TrainingStatsResponseDto {
  status: 'ok' | 'no_sessions' | 'no_data_in_period'
  summary: TrainingStatsSummaryDto
  evolution: TrainingStatsEvolutionPointDto[]
  byType: TrainingStatsTypeDto[]
  byDistance: TrainingStatsDistanceDto[]
}
