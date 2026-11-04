import api from './http'
import type { CompleteRingsSessionRequestDto } from '../dto/rings/CompleteRingsSessionRequest'
import type {
  RingsSessionHistoryResponseDto,
  RingsSessionStartedResponseDto,
  RingsSessionSummaryResponseDto,
} from '../dto/rings/RingsSessionResponse'
import type { RingsStatsResponseDto } from '../dto/rings/RingsStatsResponse'
import type {
  RingsMode,
  RingsSessionHistoryPage,
  RingsSessionStarted,
  RingsSessionSummary,
  RingsStats,
} from '../models/Rings'
import type { StatsDateRangeParams } from '../composables/useStatsDateRange'

function mapStarted(dto: RingsSessionStartedResponseDto): RingsSessionStarted {
  return { id: dto.id, mode: dto.mode, createdAt: dto.createdAt }
}

function mapSummary(dto: RingsSessionSummaryResponseDto): RingsSessionSummary {
  return {
    id: dto.id,
    mode: dto.mode,
    createdAt: dto.createdAt,
    playedAt: dto.playedAt,
    finishedAt: dto.finishedAt,
    totalScore: dto.totalScore,
    throws: dto.throws.map((t) => ({ ...t })),
  }
}

function mapStats(dto: RingsStatsResponseDto): RingsStats {
  return {
    status: dto.status,
    summary: { ...dto.summary },
    evolution: dto.evolution.map((p) => ({ ...p })),
    byTarget: dto.byTarget.map((r) => ({ ...r })),
    scoreByDecile: dto.scoreByDecile.map((d) => ({ ...d })),
  }
}

export const ringsSessionsService = {
  async start(mode: RingsMode = 'point'): Promise<RingsSessionStarted> {
    const { data } = await api.post<RingsSessionStartedResponseDto>('/rings-sessions', { mode })
    return mapStarted(data)
  },
  async current(mode: RingsMode = 'point'): Promise<RingsSessionStarted | null> {
    const { data } = await api.get<RingsSessionStartedResponseDto | null>('/rings-sessions/current', {
      params: { mode },
    })
    return data ? mapStarted(data) : null
  },
  async complete(id: number, payload: CompleteRingsSessionRequestDto): Promise<RingsSessionSummary> {
    const { data } = await api.post<RingsSessionSummaryResponseDto>(`/rings-sessions/${id}/complete`, payload)
    return mapSummary(data)
  },
  async getSummary(id: number): Promise<RingsSessionSummary> {
    const { data } = await api.get<RingsSessionSummaryResponseDto>(`/rings-sessions/${id}`)
    return mapSummary(data)
  },
  async getHistory(mode: RingsMode = 'point', page = 1, size = 20): Promise<RingsSessionHistoryPage> {
    const { data } = await api.get<RingsSessionHistoryResponseDto>('/rings-sessions/history', {
      params: { mode, page, size },
    })
    return data
  },
  async abandon(id: number): Promise<void> {
    await api.delete(`/rings-sessions/${id}`)
  },
  async getStats(range: StatsDateRangeParams, mode: RingsMode = 'point'): Promise<RingsStats> {
    const { data } = await api.get<RingsStatsResponseDto>('/rings-sessions/stats', { params: { ...range, mode } })
    return mapStats(data)
  },
}
