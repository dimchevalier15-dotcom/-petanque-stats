import api from './http'
import axios from 'axios'
import { getApiBaseUrl } from '../utils/apiBaseUrl'
import type {
  CreateLiveMatchResponseDto,
  LiveMatchResponseDto,
  UpsertLiveMatchRequestDto,
} from '../dto/live/LiveMatch'

const publicApi = axios.create({
  baseURL: getApiBaseUrl(),
})

export const liveMatchesService = {
  async create(payload: UpsertLiveMatchRequestDto): Promise<CreateLiveMatchResponseDto> {
    const { data } = await api.post<CreateLiveMatchResponseDto>('/live-matches', payload)
    return data
  },

  async update(uuid: string, payload: UpsertLiveMatchRequestDto): Promise<LiveMatchResponseDto> {
    const { data } = await api.put<LiveMatchResponseDto>(`/live-matches/${uuid}`, payload)
    return data
  },

  async get(uuid: string): Promise<LiveMatchResponseDto> {
    const { data } = await api.get<LiveMatchResponseDto>(`/live-matches/${uuid}`)
    return data
  },

  async getPublic(uuid: string): Promise<LiveMatchResponseDto> {
    const { data } = await publicApi.get<LiveMatchResponseDto>(`/live-matches/${uuid}`)
    return data
  },

  async finish(uuid: string): Promise<LiveMatchResponseDto> {
    const { data } = await api.post<LiveMatchResponseDto>(`/live-matches/${uuid}/finish`)
    return data
  },

  async delete(uuid: string): Promise<void> {
    await api.delete(`/live-matches/${uuid}`)
  },
}
