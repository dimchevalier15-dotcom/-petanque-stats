import api from './http'
import type { CompleteShootingSessionRequestDto } from '../dto/shooting/CompleteShootingSessionRequest'
import type {
  ShootingSessionHistoryResponseDto,
  ShootingSessionStartedResponseDto,
  ShootingSessionSummaryResponseDto,
} from '../dto/shooting/ShootingSessionResponse'
import type {
  ShootingSessionHistoryPage,
  ShootingSessionStarted,
  ShootingSessionSummary,
} from '../models/Shooting'

function mapStarted(dto: ShootingSessionStartedResponseDto): ShootingSessionStarted {
  return { id: dto.id, createdAt: dto.createdAt }
}

function mapSummary(dto: ShootingSessionSummaryResponseDto): ShootingSessionSummary {
  return {
    id: dto.id,
    createdAt: dto.createdAt,
    finishedAt: dto.finishedAt,
    totalScore: dto.totalScore,
    workshops: dto.workshops,
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
}
