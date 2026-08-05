import api from './http'

export type CreatePlayerInput = {
  firstName: string
  lastName: string
  nickname?: string
}

export type Player = {
  id: number
  firstName: string
  lastName: string
  nickname: string
}

export const playersService = {
  async create(input: CreatePlayerInput): Promise<Player> {
    const { data } = await api.post<Player>('/players', input)
    return data
  },
}
