import api from './http'
import type { LinkPlayerRequest } from '../dto/account/LinkPlayerRequest'
import type { UpdatePlayerProfileRequest } from '../dto/account/UpdatePlayerProfileRequest'
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

export const accountService = {
  async getLinkedPlayer(): Promise<Player | null> {
    const { data } = await api.get<PlayerItemDto | null>('/account/player')
    if (data === null || data === undefined) {
      return null
    }
    return toModel(data)
  },

  async searchUnlinkedPlayers(q: string): Promise<Player[]> {
    const { data } = await api.get<PlayerItemDto[]>('/account/players/search', { params: { q } })
    return data.map(toModel)
  },

  async linkPlayer(playerId: number): Promise<Player> {
    const payload: LinkPlayerRequest = { playerId }
    const { data } = await api.post<PlayerItemDto>('/account/player/link', payload)
    return toModel(data)
  },

  async updateProfile(payload: UpdatePlayerProfileRequest): Promise<Player> {
    const { data } = await api.put<PlayerItemDto>('/account/player', payload)
    return toModel(data)
  },

  async deleteAccount(): Promise<void> {
    await api.delete('/account')
  },
}
