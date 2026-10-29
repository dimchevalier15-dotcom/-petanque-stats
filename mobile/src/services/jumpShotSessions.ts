import api from './http'
import type {
  JumpShotSessionStartedResponseDto,
  JumpShotSessionSummaryResponseDto,
  SubmitJumpShotSeriesRequestDto,
  SubmitJumpShotSeriesResponseDto,
  RevertJumpShotLastSeriesResponseDto,
} from '../dto/jumpShot/JumpShotSessionResponse'
import type {
  JumpShotSessionHistoryResponseDto,
  JumpShotStatsResponseDto,
} from '../dto/jumpShot/JumpShotStatsResponse'
import type {
  JumpShotDistance,
  JumpShotSessionHistoryPage,
  JumpShotSessionStarted,
  JumpShotSessionSummary,
  JumpShotSeriesConsequence,
  JumpShotSeriesSummary,
  JumpShotStats,
  JumpShotThrowResult,
  JumpShotThrowSummary,
  RevertJumpShotLastSeriesResult,
  SubmitJumpShotSeriesResult,
} from '../models/JumpShot'
import type { StatsDateRangeParams } from '../composables/useStatsDateRange'

function mapThrow(dto: { position: number; result: string }): JumpShotThrowSummary {
  return {
    position: dto.position,
    result: dto.result as JumpShotThrowResult,
  }
}

function mapSeries(dto: JumpShotSessionSummaryResponseDto['series'][number]): JumpShotSeriesSummary {
  return {
    seriesNumber: dto.seriesNumber,
    distance: dto.distance as JumpShotDistance,
    successCount: dto.successCount,
    isSpecialSeries: dto.isSpecialSeries,
    consequence: dto.consequence as JumpShotSeriesConsequence,
    createdAt: dto.createdAt,
    throws: dto.throws.map(mapThrow),
  }
}

function mapStarted(dto: JumpShotSessionStartedResponseDto): JumpShotSessionStarted {
  return {
    id: dto.id,
    createdAt: dto.createdAt,
    currentDistance: dto.currentDistance as JumpShotDistance,
    seriesCount: dto.seriesCount,
  }
}

function mapSummary(dto: JumpShotSessionSummaryResponseDto): JumpShotSessionSummary {
  return {
    id: dto.id,
    createdAt: dto.createdAt,
    finishedAt: dto.finishedAt,
    won: dto.won,
    currentDistance: dto.currentDistance as JumpShotDistance,
    seriesCount: dto.seriesCount,
    series: dto.series.map(mapSeries),
  }
}

export const jumpShotSessionsService = {
  async start(): Promise<JumpShotSessionStarted> {
    const { data } = await api.post<JumpShotSessionStartedResponseDto>('/jump-shot-sessions')
    return mapStarted(data)
  },

  async current(): Promise<JumpShotSessionStarted | null> {
    const { data } = await api.get<JumpShotSessionStartedResponseDto | null>('/jump-shot-sessions/current')
    return data ? mapStarted(data) : null
  },

  async getSummary(id: number): Promise<JumpShotSessionSummary> {
    const { data } = await api.get<JumpShotSessionSummaryResponseDto>(`/jump-shot-sessions/${id}`)
    return mapSummary(data)
  },

  async submitSeries(id: number, payload: SubmitJumpShotSeriesRequestDto): Promise<SubmitJumpShotSeriesResult> {
    const { data } = await api.post<SubmitJumpShotSeriesResponseDto>(`/jump-shot-sessions/${id}/series`, payload)
    return {
      series: mapSeries(data.series),
      nextDistance: data.nextDistance as JumpShotDistance,
      sessionWon: data.sessionWon,
      sessionFinished: data.sessionFinished,
      session: mapSummary(data.session),
    }
  },

  async abandon(id: number): Promise<void> {
    await api.delete(`/jump-shot-sessions/${id}`)
  },

  async revertLastSeries(id: number): Promise<RevertJumpShotLastSeriesResult> {
    const { data } = await api.delete<RevertJumpShotLastSeriesResponseDto>(`/jump-shot-sessions/${id}/series/last`)
    return {
      session: mapSummary(data.session),
      restoredThrows: data.restoredThrows.map(mapThrow),
    }
  },

  async getHistory(page = 1, size = 20): Promise<JumpShotSessionHistoryPage> {
    const { data } = await api.get<JumpShotSessionHistoryResponseDto>('/jump-shot-sessions/history', {
      params: { page, size },
    })
    return {
      ...data,
      items: data.items.map((item) => ({ ...item })),
    }
  },

  async getStats(range: StatsDateRangeParams): Promise<JumpShotStats> {
    const { data } = await api.get<JumpShotStatsResponseDto>('/jump-shot-sessions/stats', { params: range })
    return {
      status: data.status,
      summary: { ...data.summary },
      evolution: data.evolution.map((p) => ({ ...p })),
      byDistance: data.byDistance.map((d) => ({
        distance: d.distance as JumpShotDistance,
        throwCount: d.throwCount,
        successRate: d.successRate,
      })),
      byResult: data.byResult.map((r) => ({
        result: r.result as JumpShotThrowResult,
        count: r.count,
      })),
    }
  },
}
