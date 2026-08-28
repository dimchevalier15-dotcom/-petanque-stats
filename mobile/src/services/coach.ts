import api from './http'
import type { StatsDateRangeParams } from '../composables/useStatsDateRange'
import type { MatchNature } from '../models/MatchContext'
import type { MatchType } from '../models/Match'
import type { DistanceBucketKey } from '../models/PlayerStats'
import type { PlayerStatsResponseDto } from '../dto/player/PlayerStatsResponse'
import type { MatchHistoryResponseDto } from '../dto/match/MatchHistoryResponse'
import type { CoachPlayerListResponseDto } from '../dto/coach/CoachPlayerListResponse'
import type { CoachPlayerList } from '../models/Coach'
import type { CreatePlayerResponseDto } from '../dto/player/CreatePlayerResponse'
import type { PlayerItemDto } from '../dto/player/PlayerItem'
import type { Player } from '../models/Player'
import type { PlayerStats } from '../models/PlayerStats'
import type { MatchHistoryPage } from '../models/MatchHistory'
import { mapPlayerStats } from './stats'

function mapCoachList(dto: CoachPlayerListResponseDto): CoachPlayerList {
  return {
    clubId: dto.clubId,
    clubName: dto.clubName,
    from: dto.from,
    to: dto.to,
    items: dto.items.map((item) => ({
      id: item.id,
      firstName: item.firstName,
      lastName: item.lastName,
      nickname: item.nickname,
      point: { ...item.point },
      tir: { ...item.tir },
    })),
  }
}

function mapHistoryPage(dto: MatchHistoryResponseDto): MatchHistoryPage {
  return {
    page: dto.page,
    pageSize: dto.pageSize,
    total: dto.total,
    items: dto.items.map((item) => ({ ...item })),
  }
}

export interface CreateCoachPlayerRequest {
  firstName: string
  lastName: string
  nickname?: string
}

export const coachService = {
  async listPlayers(
    range: StatsDateRangeParams,
    nature?: MatchNature | 'all',
  ): Promise<CoachPlayerList> {
    const { data } = await api.get<CoachPlayerListResponseDto>('/coach/players', {
      params: {
        ...range,
        ...(nature && nature !== 'all' ? { nature } : {}),
      },
    })
    return mapCoachList(data)
  },

  async createPlayer(payload: CreateCoachPlayerRequest): Promise<Player> {
    const { data } = await api.post<CreatePlayerResponseDto>('/coach/players', payload)
    return {
      id: data.id,
      firstName: data.firstName,
      lastName: data.lastName,
      nickname: data.nickname,
      clubId: data.clubId,
      clubName: data.clubName,
    }
  },

  async searchAvailablePlayers(query: string): Promise<Player[]> {
    const { data } = await api.get<PlayerItemDto[]>('/coach/players/available', {
      params: { q: query },
    })
    return data.map((item) => ({
      id: item.id,
      firstName: item.firstName,
      lastName: item.lastName,
      nickname: item.nickname,
      clubId: item.clubId ?? null,
      clubName: item.clubName ?? null,
    }))
  },

  async attachPlayer(playerId: number): Promise<Player> {
    const { data } = await api.post<PlayerItemDto>(`/coach/players/${playerId}/attach`)
    return {
      id: data.id,
      firstName: data.firstName,
      lastName: data.lastName,
      nickname: data.nickname,
      clubId: data.clubId ?? null,
      clubName: data.clubName ?? null,
    }
  },

  async getPlayerStats(
    playerId: number,
    range: StatsDateRangeParams,
    nature?: MatchNature | 'all',
    type?: MatchType | 'all',
    distance?: DistanceBucketKey | 'all',
    competitionId?: number | 'all',
  ): Promise<PlayerStats> {
    const { data } = await api.get<PlayerStatsResponseDto>(`/coach/players/${playerId}/stats`, {
      params: {
        ...range,
        ...(nature && nature !== 'all' ? { nature } : {}),
        ...(type && type !== 'all' ? { type } : {}),
        ...(distance && distance !== 'all' ? { distance } : {}),
        ...(competitionId && competitionId !== 'all' ? { competitionId } : {}),
      },
    })
    return mapPlayerStats(data)
  },

  async getPlayerHistory(playerId: number, page = 1, pageSize = 20): Promise<MatchHistoryPage> {
    const { data } = await api.get<MatchHistoryResponseDto>(`/coach/players/${playerId}/matches/history`, {
      params: { page, pageSize },
    })
    return mapHistoryPage(data)
  },
}
