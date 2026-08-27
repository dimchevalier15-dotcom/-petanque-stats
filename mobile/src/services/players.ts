import api from './http'
import type { CreatePlayerRequest } from '../dto/player/CreatePlayerRequest'
import type { CreatePlayerResponseDto } from '../dto/player/CreatePlayerResponse'
import type { PlayerItemDto } from '../dto/player/PlayerItem'
import type { Player } from '../models/Player'

function toModel(dto: PlayerItemDto): Player {
  return {
    id: dto.id,
    firstName: dto.firstName,
    lastName: dto.lastName,
    nickname: dto.nickname,
    clubId: dto.clubId ?? null,
    clubName: dto.clubName ?? null,
  }
}

export const playersService = {
  async create(payload: CreatePlayerRequest): Promise<Player> {
    const { data } = await api.post<CreatePlayerResponseDto>('/players', payload)
    return toModel(data)
  },
  async search(q: string, options?: { unlinkedOnly?: boolean }): Promise<Player[]> {
    const { data } = await api.get<PlayerItemDto[]>('/players', {
      params: { q, unlinkedOnly: options?.unlinkedOnly ? true : undefined },
    })
    return data.map(toModel)
  },
  async getById(id: number): Promise<Player> {
    const { data } = await api.get<PlayerItemDto>(`/players/${id}`)
    return toModel(data)
  },
}
