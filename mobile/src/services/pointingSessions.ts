import api from './http'
import type { CompletePointingSessionRequestDto } from '../dto/pointing/CompletePointingSessionRequest'
import type {
  PointingSessionHistoryResponseDto,
  PointingSessionStartedResponseDto,
  PointingSessionSummaryResponseDto,
  UpdatePointingSessionContextRequestDto,
} from '../dto/pointing/PointingSessionResponse'
import type { PointingStatsResponseDto } from '../dto/pointing/PointingStatsResponse'
import type {
  PointingContextNature,
  PointingSessionHistoryPage,
  PointingSessionStarted,
  PointingSessionSummary,
  PointingStats,
} from '../models/Pointing'
import type { StatsDateRangeParams } from '../composables/useStatsDateRange'

function mapStarted(dto: PointingSessionStartedResponseDto): PointingSessionStarted {
  return { id: dto.id, createdAt: dto.createdAt }
}

function mapSummary(dto: PointingSessionSummaryResponseDto): PointingSessionSummary {
  return {
    id: dto.id,
    createdAt: dto.createdAt,
    playedAt: dto.playedAt,
    finishedAt: dto.finishedAt,
    totalScore: dto.totalScore,
    contextNature: dto.contextNature,
    title: dto.title,
    description: dto.description,
    workshops: dto.workshops,
  }
}

function mapStats(dto: PointingStatsResponseDto): PointingStats {
  return {
    status: dto.status,
    summary: { ...dto.summary },
    evolution: dto.evolution.map((p) => ({ ...p })),
    byWorkshop: dto.byWorkshop.map((w) => ({ ...w })),
    byDistance: dto.byDistance.map((d) => ({ ...d })),
    byResult: dto.byResult.map((r) => ({
      result: r.result as PointingStats['byResult'][number]['result'],
      count: r.count,
    })),
    heatmap: dto.heatmap.map((c) => ({ ...c })),
  }
}

export const pointingSessionsService = {
  async start(): Promise<PointingSessionStarted> {
    const { data } = await api.post<PointingSessionStartedResponseDto>('/pointing-sessions')
    return mapStarted(data)
  },
  async current(): Promise<PointingSessionStarted | null> {
    const { data } = await api.get<PointingSessionStartedResponseDto | null>('/pointing-sessions/current')
    return data ? mapStarted(data) : null
  },
  async complete(id: number, payload: CompletePointingSessionRequestDto): Promise<PointingSessionSummary> {
    const { data } = await api.post<PointingSessionSummaryResponseDto>(`/pointing-sessions/${id}/complete`, payload)
    return mapSummary(data)
  },
  async getSummary(id: number): Promise<PointingSessionSummary> {
    const { data } = await api.get<PointingSessionSummaryResponseDto>(`/pointing-sessions/${id}`)
    return mapSummary(data)
  },
  async getHistory(page = 1, size = 20): Promise<PointingSessionHistoryPage> {
    const { data } = await api.get<PointingSessionHistoryResponseDto>('/pointing-sessions/history', {
      params: { page, size },
    })
    return data
  },
  async abandon(id: number): Promise<void> {
    await api.delete(`/pointing-sessions/${id}`)
  },
  async updateContext(id: number, payload: UpdatePointingSessionContextRequestDto): Promise<PointingSessionSummary> {
    const { data } = await api.put<PointingSessionSummaryResponseDto>(`/pointing-sessions/${id}/context`, payload)
    return mapSummary(data)
  },
  async getStats(range: StatsDateRangeParams, nature?: PointingContextNature | 'all'): Promise<PointingStats> {
    const { data } = await api.get<PointingStatsResponseDto>('/pointing-sessions/stats', {
      params: {
        ...range,
        ...(nature && nature !== 'all' ? { nature } : {}),
      },
    })
    return mapStats(data)
  },
}
