import api from './http'
import type { CreateMatchRequestDto } from '../dto/match/CreateMatchRequest'
import type { CreateMatchResponseDto } from '../dto/match/CreateMatchResponse'
import type { CompleteMatchRequestDto } from '../dto/match/CompleteMatchRequest'

export const matchesService = {
  async create(payload: CreateMatchRequestDto): Promise<CreateMatchResponseDto> {
    const { data } = await api.post<CreateMatchResponseDto>('/matches', payload)
    return data
  },
  async complete(matchId: number, payload: CompleteMatchRequestDto): Promise<{ id: number }> {
    const { data } = await api.post<{ id: number }>(`/matches/${matchId}/complete`, payload)
    return data
  },
}
