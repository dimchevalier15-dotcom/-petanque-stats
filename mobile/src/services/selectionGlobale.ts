import api from './http'
import type { SelectionGlobaleItemDto } from '../dto/selectionGlobale/SelectionGlobaleItem'
import type { PlayerItemDto } from '../dto/player/PlayerItem'
import type { SelectionGlobaleDetail, SelectionGlobaleItem } from '../models/SelectionGlobale'
import type { Player } from '../models/Player'

function mapItem(dto: SelectionGlobaleItemDto): SelectionGlobaleItem {
  return { id: dto.id, name: dto.name }
}

function mapPlayer(dto: PlayerItemDto): Player {
  return {
    id: dto.id,
    firstName: dto.firstName,
    lastName: dto.lastName,
    nickname: dto.nickname,
    clubId: dto.clubId ?? null,
    clubName: dto.clubName ?? null,
  }
}

export const selectionGlobaleService = {
  async listForCoach(): Promise<SelectionGlobaleItem[]> {
    const { data } = await api.get<SelectionGlobaleItemDto[]>('/coach/selections-globales')
    return data.map(mapItem)
  },

  async listAdmin(): Promise<SelectionGlobaleItem[]> {
    const { data } = await api.get<SelectionGlobaleItemDto[]>('/selections-globales')
    return data.map(mapItem)
  },

  async getDetail(id: number): Promise<SelectionGlobaleDetail> {
    const { data } = await api.get<{
      id: number
      name: string
      coaches: { userId: number; email: string }[]
      players: PlayerItemDto[]
    }>(`/selections-globales/${id}`)
    return {
      id: data.id,
      name: data.name,
      coaches: data.coaches.map((c) => ({ userId: c.userId, email: c.email })),
      players: data.players.map(mapPlayer),
    }
  },

  async create(name: string): Promise<SelectionGlobaleItem> {
    const { data } = await api.post<SelectionGlobaleItemDto>('/selections-globales', { name })
    return mapItem(data)
  },

  async update(id: number, name: string): Promise<SelectionGlobaleItem> {
    const { data } = await api.put<SelectionGlobaleItemDto>(`/selections-globales/${id}`, { name })
    return mapItem(data)
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/selections-globales/${id}`)
  },

  async addCoach(selectionId: number, email: string): Promise<SelectionGlobaleDetail> {
    await api.post(`/selections-globales/${selectionId}/coaches`, { email })
    return this.getDetail(selectionId)
  },

  async removeCoach(selectionId: number, email: string): Promise<SelectionGlobaleDetail> {
    await api.delete(`/selections-globales/${selectionId}/coaches`, { data: { email } })
    return this.getDetail(selectionId)
  },

  async addPlayer(selectionId: number, playerId: number): Promise<SelectionGlobaleDetail> {
    await api.post(`/selections-globales/${selectionId}/players/${playerId}`)
    return this.getDetail(selectionId)
  },

  async removePlayer(selectionId: number, playerId: number): Promise<SelectionGlobaleDetail> {
    await api.delete(`/selections-globales/${selectionId}/players/${playerId}`)
    return this.getDetail(selectionId)
  },
}
