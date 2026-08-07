import api from './http'
import type { CreateMatchRequestDto } from '../dto/match/CreateMatchRequest'
import type { CreateMatchResponseDto } from '../dto/match/CreateMatchResponse'
import type { CompleteMatchRequestDto } from '../dto/match/CompleteMatchRequest'
import type { MatchSummaryResponseDto } from '../dto/match/MatchSummaryResponse'
import type { MatchSummary } from '../models/MatchSummary'
import type { MatchHistoryResponseDto, MatchHistoryItemDto } from '../dto/match/MatchHistoryResponse'
import type { MatchHistoryItem, MatchHistoryPage } from '../models/MatchHistory'

function mapHistoryItem(dto: MatchHistoryItemDto): MatchHistoryItem {
  return {
    id: dto.id,
    date: dto.date,
    type: dto.type,
    scoreA: dto.scoreA,
    scoreB: dto.scoreB,
    winner: dto.winner,
    victory: dto.victory,
  }
}

export const matchesService = {
  async create(payload: CreateMatchRequestDto): Promise<CreateMatchResponseDto> {
    const { data } = await api.post<CreateMatchResponseDto>('/matches', payload)
    return data
  },
  async complete(matchId: number, payload: CompleteMatchRequestDto): Promise<{ id: number }> {
    const { data } = await api.post<{ id: number }>(`/matches/${matchId}/complete`, payload)
    return data
  },
  async getSummary(matchId: number): Promise<MatchSummary> {
    const { data } = await api.get<MatchSummaryResponseDto>(`/matches/${matchId}/summary`)
    // DTO -> Model mapping (same shape currently)
    return data as unknown as MatchSummary
  },
  async getHistory(page = 1, size = 20): Promise<MatchHistoryPage> {
    const { data } = await api.get<MatchHistoryResponseDto>('/matches/history', { params: { page, size } })
    const items = data.items.map(mapHistoryItem)
    return { page: data.page, pageSize: data.pageSize, total: data.total, items }
  },
}
