import api from './http'
import type { CompetitionItemDto } from '../dto/competition/CompetitionItem'
import type { UpsertCompetitionRequestDto } from '../dto/competition/UpsertCompetitionRequest'
import type { Competition } from '../models/Competition'

function mapCompetition(dto: CompetitionItemDto): Competition {
  return {
    id: dto.id,
    name: dto.name,
    eventDate: dto.eventDate,
    country: dto.country,
    context: dto.context,
  }
}

export const competitionsService = {
  async list(): Promise<Competition[]> {
    const { data } = await api.get<CompetitionItemDto[]>('/competitions')
    return data.map(mapCompetition)
  },

  async create(payload: UpsertCompetitionRequestDto): Promise<Competition> {
    const { data } = await api.post<CompetitionItemDto>('/competitions', payload)
    return mapCompetition(data)
  },

  async update(id: number, payload: UpsertCompetitionRequestDto): Promise<Competition> {
    const { data } = await api.put<CompetitionItemDto>(`/competitions/${id}`, payload)
    return mapCompetition(data)
  },

  async remove(id: number): Promise<void> {
    await api.delete(`/competitions/${id}`)
  },
}
