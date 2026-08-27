import type { LiveMatchData } from '../models/LiveMatch'

export interface UpsertLiveMatchRequestDto {
  data: LiveMatchData
}

export interface CreateLiveMatchResponseDto {
  uuid: string
  url: string
}

export interface LiveMatchResponseDto {
  uuid: string
  status: 'active' | 'finished'
  data: LiveMatchData
  createdAt: string
  updatedAt: string
  finishedAt: string | null
}
