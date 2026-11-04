import api from './http'
import type { CompleteRingsSessionRequestDto } from '../dto/rings/CompleteRingsSessionRequest'
import type {
  RingsSessionHistoryResponseDto,
  RingsSessionStartedResponseDto,
  RingsSessionSummaryResponseDto,
} from '../dto/rings/RingsSessionResponse'
import type { RingsSessionHistoryPage, RingsSessionStarted, RingsSessionSummary } from '../models/Rings'

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

export const ringsSessionsService = {
  async start(): Promise<RingsSessionStarted> {
    const { data } = await api.post<RingsSessionStartedResponseDto>('/rings-sessions', { mode: 'point' })
    return mapStarted(data)
  },
  async current(): Promise<RingsSessionStarted | null> {
    const { data } = await api.get<RingsSessionStartedResponseDto | null>('/rings-sessions/current')
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
  async getHistory(page = 1, size = 20): Promise<RingsSessionHistoryPage> {
    const { data } = await api.get<RingsSessionHistoryResponseDto>('/rings-sessions/history', {
      params: { page, size },
    })
    return data
  },
  async abandon(id: number): Promise<void> {
    await api.delete(`/rings-sessions/${id}`)
  },
}
