import api from './http'
import type { CompleteShootingSessionRequestDto } from '../dto/shooting/CompleteShootingSessionRequest'
import type {
  ShootingSessionHistoryResponseDto,
  ShootingSessionStartedResponseDto,
  ShootingSessionSummaryResponseDto,
  UpdateShootingSessionContextRequestDto,
} from '../dto/shooting/ShootingSessionResponse'
import type { ShootingStatsResponseDto } from '../dto/shooting/ShootingStatsResponse'
import type {
  ShootingContextNature,
  ShootingSessionHistoryPage,
  ShootingSessionStarted,
  ShootingSessionSummary,
  ShootingStats,
} from '../models/Shooting'
import type { StatsDateRangeParams } from '../composables/useStatsDateRange'

function mapStarted(dto: ShootingSessionStartedResponseDto): ShootingSessionStarted {
  return { id: dto.id, createdAt: dto.createdAt }
}

function mapSummary(dto: ShootingSessionSummaryResponseDto): ShootingSessionSummary {
  return {
    id: dto.id,
    createdAt: dto.createdAt,
    finishedAt: dto.finishedAt,
    totalScore: dto.totalScore,
    contextNature: dto.contextNature,
    title: dto.title,
    description: dto.description,
    workshops: dto.workshops,
  }
}

function mapStats(dto: ShootingStatsResponseDto): ShootingStats {
  return {
    status: dto.status,
    summary: { ...dto.summary },
    evolution: dto.evolution.map((p) => ({ ...p })),
    byWorkshop: dto.byWorkshop.map((w) => ({ ...w })),
    byDistance: dto.byDistance.map((d) => ({ ...d })),
    byResult: dto.byResult.map((r) => ({
      result: r.result as ShootingStats['byResult'][number]['result'],
      count: r.count,
    })),
    heatmap: dto.heatmap.map((c) => ({ ...c })),
  }
}

export const shootingSessionsService = {
  async start(): Promise<ShootingSessionStarted> {
    const { data } = await api.post<ShootingSessionStartedResponseDto>('/shooting-sessions')
    return mapStarted(data)
  },
  async current(): Promise<ShootingSessionStarted | null> {
    const { data } = await api.get<ShootingSessionStartedResponseDto | null>('/shooting-sessions/current')
    return data ? mapStarted(data) : null
  },
  async complete(id: number, payload: CompleteShootingSessionRequestDto): Promise<ShootingSessionSummary> {
    const { data } = await api.post<ShootingSessionSummaryResponseDto>(`/shooting-sessions/${id}/complete`, payload)
    return mapSummary(data)
  },
  async getSummary(id: number): Promise<ShootingSessionSummary> {
    const { data } = await api.get<ShootingSessionSummaryResponseDto>(`/shooting-sessions/${id}`)
    return mapSummary(data)
  },
  async getHistory(page = 1, size = 20): Promise<ShootingSessionHistoryPage> {
    const { data } = await api.get<ShootingSessionHistoryResponseDto>('/shooting-sessions/history', {
      params: { page, size },
    })
    return data
  },
  async abandon(id: number): Promise<void> {
    await api.delete(`/shooting-sessions/${id}`)
  },
  async updateContext(id: number, payload: UpdateShootingSessionContextRequestDto): Promise<ShootingSessionSummary> {
    const { data } = await api.put<ShootingSessionSummaryResponseDto>(`/shooting-sessions/${id}/context`, payload)
    return mapSummary(data)
  },
  async getStats(range: StatsDateRangeParams, nature?: ShootingContextNature | 'all'): Promise<ShootingStats> {
    const { data } = await api.get<ShootingStatsResponseDto>('/shooting-sessions/stats', {
      params: {
        ...range,
        ...(nature && nature !== 'all' ? { nature } : {}),
      },
    })
    return mapStats(data)
  },
}
