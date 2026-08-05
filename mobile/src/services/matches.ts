import api from './http'

export type MatchType = 'tete_a_tete' | 'doublette' | 'triplette'

export type CreateMatchPayload = {
  type: MatchType
  targetScore: number
  teamA: number[]
  teamB: number[]
}

export const matchesService = {
  async create(payload: CreateMatchPayload): Promise<{ id: number }> {
    const { data } = await api.post<{ id: number }>('/matches', payload)
    return data
  },
}
