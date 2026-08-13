import type { TrainingType } from '../../models/Training'

export interface TrainingSessionStartedResponseDto {
  id: number
  type: TrainingType
  distance: number
  plannedBalls: number
  createdAt: string
  attemptsCount: number
  currentScore: number
}

export interface TrainingAttemptSummaryDto {
  number: number
  result: string
  score: number
}

export interface TrainingSessionSummaryResponseDto {
  id: number
  type: TrainingType
  distance: number
  plannedBalls: number
  createdAt: string
  finishedAt: string | null
  totalScore: number | null
  successfulBalls: number
  successRate: number | null
  attempts: TrainingAttemptSummaryDto[]
}

export interface RecordTrainingAttemptResponseDto {
  number: number
  result: string
  score: number
  currentScore: number
  attemptsCount: number
  sessionFinished: boolean
  summary: TrainingSessionSummaryResponseDto | null
}

export interface TrainingSessionHistoryItemDto {
  id: number
  type: TrainingType
  distance: number
  plannedBalls: number
  createdAt: string
  finishedAt: string
  totalScore: number
  successfulBalls: number
  successRate: number
}

export interface TrainingSessionHistoryResponseDto {
  page: number
  pageSize: number
  total: number
  items: TrainingSessionHistoryItemDto[]
}
