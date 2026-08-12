export type TrainingType = 'point' | 'tir'

export type TrainingPointResult =
  | 'perfect'
  | 'very_good'
  | 'good'
  | 'acceptable'
  | 'bad'
  | 'useless'

export type TrainingTirResult = 'missed' | 'touched' | 'successful' | 'palet' | 'carreau'

export type TrainingResult = TrainingPointResult | TrainingTirResult

export const TRAINING_DISTANCES = [6, 7, 8, 9, 10] as const

export const TRAINING_BALL_COUNTS = [5, 10, 15, 20, 25, 30] as const

export const TRAINING_POINT_RESULTS: TrainingPointResult[] = [
  'perfect',
  'very_good',
  'good',
  'acceptable',
  'bad',
  'useless',
]

export const TRAINING_TIR_RESULTS: TrainingTirResult[] = ['missed', 'touched', 'successful', 'palet', 'carreau']

export interface TrainingSessionStarted {
  id: number
  type: TrainingType
  distance: number
  plannedBalls: number
  createdAt: string
  attemptsCount: number
  currentScore: number
}

export interface TrainingAttemptSummary {
  number: number
  result: string
  score: number
}

export interface TrainingSessionSummary {
  id: number
  type: TrainingType
  distance: number
  plannedBalls: number
  createdAt: string
  finishedAt: string | null
  totalScore: number | null
  successfulBalls: number
  successRate: number | null
  attempts: TrainingAttemptSummary[]
}

export interface RecordTrainingAttemptResult {
  number: number
  result: string
  score: number
  currentScore: number
  attemptsCount: number
  sessionFinished: boolean
  summary: TrainingSessionSummary | null
}

export interface TrainingSessionHistoryItem {
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

export interface TrainingSessionHistoryPage {
  page: number
  pageSize: number
  total: number
  items: TrainingSessionHistoryItem[]
}

export interface TrainingStatsSummary {
  sessionsCount: number
  totalBalls: number
  successfulBalls: number
  successRate: number | null
  bestScore: number | null
  averageScore: number | null
}

export interface TrainingStatsEvolutionPoint {
  sessionId: number
  date: string
  totalScore: number
  plannedBalls: number
  successRate: number
}

export interface TrainingStatsTypeBreakdown {
  type: TrainingType
  ballCount: number
  successRate: number
  averageScore: number
}

export interface TrainingStatsDistanceBreakdown {
  distance: number
  ballCount: number
  successRate: number
  averageScore: number
}

export interface TrainingStats {
  status: 'ok' | 'no_sessions' | 'no_data_in_period'
  summary: TrainingStatsSummary
  evolution: TrainingStatsEvolutionPoint[]
  byType: TrainingStatsTypeBreakdown[]
  byDistance: TrainingStatsDistanceBreakdown[]
}

export function resultsForType(type: TrainingType): TrainingResult[] {
  return type === 'point' ? TRAINING_POINT_RESULTS : TRAINING_TIR_RESULTS
}

export function scoreFor(type: TrainingType, result: TrainingResult): number {
  if (type === 'point') {
    switch (result) {
      case 'perfect':
        return 2
      case 'very_good':
      case 'good':
        return 1
      case 'acceptable':
        return 0
      case 'bad':
        return -1
      case 'useless':
        return -2
      default:
        return 0
    }
  }
  switch (result) {
    case 'carreau':
      return 3
    case 'palet':
      return 2
    case 'successful':
      return 1
    default:
      return 0
  }
}

export function resultSeverity(
  type: TrainingType,
  result: TrainingResult,
): 'danger' | 'warn' | 'secondary' | 'success' | 'help' {
  if (type === 'point') {
    switch (result) {
      case 'perfect':
        return 'help'
      case 'very_good':
      case 'good':
        return 'success'
      case 'acceptable':
        return 'secondary'
      case 'bad':
        return 'warn'
      default:
        return 'danger'
    }
  }
  switch (result) {
    case 'carreau':
      return 'help'
    case 'palet':
      return 'success'
    case 'successful':
      return 'success'
    case 'touched':
      return 'warn'
    default:
      return 'danger'
  }
}
