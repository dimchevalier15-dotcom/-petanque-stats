import api from './http'
import type { CompletePointingSessionRequestDto } from '../dto/pointing/CompletePointingSessionRequest'
import type {
  PointingSessionStartedResponseDto,
  PointingSessionSummaryResponseDto,
} from '../dto/pointing/PointingSessionResponse'
import type { PointingSessionStarted, PointingSessionSummary } from '../models/Pointing'

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
  async abandon(id: number): Promise<void> {
    await api.delete(`/pointing-sessions/${id}`)
  },
}
