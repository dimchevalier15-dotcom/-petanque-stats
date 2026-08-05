import api from './http'

export type Player = {
  id: number
  firstName: string
  lastName: string
  nickname: string
}

export const playersService = {
  async create(payload: { firstName: string; lastName: string; nickname?: string }): Promise<Player> {
    const { data } = await api.post<Player>('/players', payload)
    return data
  },
  async search(q: string): Promise<Player[]> {
    const { data } = await api.get<Player[]>('/players', { params: { q } })
    return data
  },
  async getById(id: number): Promise<Player> {
    const { data } = await api.get<Player>(`/players/${id}`)
    return data
  },
}
