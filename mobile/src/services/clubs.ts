import api from './http'
import type { ClubItemDto } from '../dto/club/ClubItem'
import type { UpsertClubRequestDto } from '../dto/club/UpsertClubRequest'
import type { Club } from '../models/Club'

function mapClub(dto: ClubItemDto): Club {
  return {
    id: dto.id,
    name: dto.name,
    description: dto.description,
    country: {
      id: dto.country.id,
      isoCode: dto.country.isoCode,
      name: dto.country.name,
    },
  }
}

export const clubsService = {
  async list(): Promise<Club[]> {
    const { data } = await api.get<ClubItemDto[]>('/clubs')
    return data.map(mapClub)
  },

  async create(payload: UpsertClubRequestDto): Promise<Club> {
    const { data } = await api.post<ClubItemDto>('/clubs', payload)
    return mapClub(data)
  },

  async update(id: number, payload: UpsertClubRequestDto): Promise<Club> {
    const { data } = await api.put<ClubItemDto>(`/clubs/${id}`, payload)
    return mapClub(data)
  },

  async remove(id: number): Promise<void> {
    await api.delete(`/clubs/${id}`)
  },
}
