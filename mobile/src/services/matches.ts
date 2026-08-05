import api from './http'
import type { CreateMatchRequestDto } from '../dto/match/CreateMatchRequest'
import type { CreateMatchResponseDto } from '../dto/match/CreateMatchResponse'

export const matchesService = {
  async create(payload: CreateMatchRequestDto): Promise<CreateMatchResponseDto> {
    const { data } = await api.post<CreateMatchResponseDto>('/matches', payload)
    return data
  },
}
