import api from './http'
import type { CreateTrainingSessionRequestDto, RecordTrainingAttemptRequestDto } from '../dto/training/TrainingRequest'
import type {
  RecordTrainingAttemptResponseDto,
  TrainingSessionHistoryResponseDto,
  TrainingSessionStartedResponseDto,
  TrainingSessionSummaryResponseDto,
} from '../dto/training/TrainingSessionResponse'
import type { TrainingStatsResponseDto } from '../dto/training/TrainingStatsResponse'
import type {
  RecordTrainingAttemptResult,
  TrainingSessionHistoryPage,
  TrainingSessionStarted,
  TrainingSessionSummary,
  TrainingStats,
  TrainingType,
} from '../models/Training'
import type { StatsDateRangeParams } from '../composables/useStatsDateRange'

function mapStarted(dto: TrainingSessionStartedResponseDto): TrainingSessionStarted {
  return {
    id: dto.id,
    type: dto.type,
    distance: dto.distance,
    plannedBalls: dto.plannedBalls,
    createdAt: dto.createdAt,
    attemptsCount: dto.attemptsCount,
    currentScore: dto.currentScore,
  }
}

function mapSummary(dto: TrainingSessionSummaryResponseDto): TrainingSessionSummary {
  return {
    id: dto.id,
    type: dto.type,
    distance: dto.distance,
    plannedBalls: dto.plannedBalls,
    createdAt: dto.createdAt,
    finishedAt: dto.finishedAt,
    totalScore: dto.totalScore,
    successfulBalls: dto.successfulBalls,
    successRate: dto.successRate,
    attempts: dto.attempts.map((a) => ({ ...a })),
  }
}

function mapAttemptResult(dto: RecordTrainingAttemptResponseDto): RecordTrainingAttemptResult {
  return {
    number: dto.number,
    result: dto.result,
    score: dto.score,
    currentScore: dto.currentScore,
    attemptsCount: dto.attemptsCount,
    sessionFinished: dto.sessionFinished,
    summary: dto.summary ? mapSummary(dto.summary) : null,
  }
}

function mapStats(dto: TrainingStatsResponseDto): TrainingStats {
  return {
    status: dto.status,
    summary: { ...dto.summary },
    evolution: dto.evolution.map((p) => ({ ...p })),
    byType: dto.byType.map((t) => ({ ...t })),
    byDistance: dto.byDistance.map((d) => ({ ...d })),
  }
}

export const trainingSessionsService = {
  async create(payload: CreateTrainingSessionRequestDto): Promise<TrainingSessionStarted> {
    const { data } = await api.post<TrainingSessionStartedResponseDto>('/training-sessions', payload)
    return mapStarted(data)
  },
  async current(): Promise<TrainingSessionStarted | null> {
    const { data } = await api.get<TrainingSessionStartedResponseDto | null>('/training-sessions/current')
    return data ? mapStarted(data) : null
  },
  async recordAttempt(id: number, payload: RecordTrainingAttemptRequestDto): Promise<RecordTrainingAttemptResult> {
    const { data } = await api.post<RecordTrainingAttemptResponseDto>(`/training-sessions/${id}/attempts`, payload)
    return mapAttemptResult(data)
  },
  async getSummary(id: number): Promise<TrainingSessionSummary> {
    const { data } = await api.get<TrainingSessionSummaryResponseDto>(`/training-sessions/${id}`)
    return mapSummary(data)
  },
  async getHistory(page = 1, size = 20): Promise<TrainingSessionHistoryPage> {
    const { data } = await api.get<TrainingSessionHistoryResponseDto>('/training-sessions/history', {
      params: { page, size },
    })
    return data
  },
  async abandon(id: number): Promise<void> {
    await api.delete(`/training-sessions/${id}`)
  },
  async getStats(range: StatsDateRangeParams, type?: TrainingType | 'all'): Promise<TrainingStats> {
    const params: Record<string, string> = { ...range }
    if (type && type !== 'all') {
      params.type = type
    }
    const { data } = await api.get<TrainingStatsResponseDto>('/training-sessions/stats', { params })
    return mapStats(data)
  },
}
