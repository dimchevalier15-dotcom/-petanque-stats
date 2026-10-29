import api from './http'
import type { StatsDateRangeParams } from '../composables/useStatsDateRange'
import type { MatchNature } from '../models/MatchContext'
import type { MatchType } from '../models/Match'
import type { DistanceBucketKey } from '../models/PlayerStats'
import type { PlayerStatsResponseDto } from '../dto/player/PlayerStatsResponse'
import type { PlayerTacticalInsightsResponseDto } from '../dto/player/PlayerTacticalInsightsResponse'
import type { MatchHistoryResponseDto } from '../dto/match/MatchHistoryResponse'
import type { CoachPlayerListResponseDto } from '../dto/coach/CoachPlayerListResponse'
import type { JumpShotStatsResponseDto } from '../dto/jumpShot/JumpShotStatsResponse'
import type { ShootingStatsResponseDto } from '../dto/shooting/ShootingStatsResponse'
import type { PointingStatsResponseDto } from '../dto/pointing/PointingStatsResponse'
import type { CoachPlayerList } from '../models/Coach'
import type { JumpShotDistance, JumpShotStats, JumpShotThrowResult } from '../models/JumpShot'
import type { PointingContextNature, PointingStats } from '../models/Pointing'
import type { CreatePlayerResponseDto } from '../dto/player/CreatePlayerResponse'
import type { PlayerItemDto } from '../dto/player/PlayerItem'
import type { Player } from '../models/Player'
import type { PlayerStats } from '../models/PlayerStats'
import type { ShootingContextNature, ShootingStats } from '../models/Shooting'
import type { PlayerTacticalInsights } from '../models/PlayerTacticalInsights'
import type { MatchHistoryPage } from '../models/MatchHistory'
import { mapPlayerStats } from './stats'

function mapPointingStats(dto: PointingStatsResponseDto): PointingStats {
  return {
    status: dto.status,
    summary: { ...dto.summary },
    evolution: dto.evolution.map((p) => ({ ...p })),
    byWorkshop: dto.byWorkshop.map((w) => ({ ...w })),
    byDistance: dto.byDistance.map((d) => ({ ...d })),
    byResult: dto.byResult.map((r) => ({
      result: r.result as PointingStats['byResult'][number]['result'],
      count: r.count,
    })),
    heatmap: dto.heatmap.map((c) => ({ ...c })),
  }
}

function mapShootingStats(dto: ShootingStatsResponseDto): ShootingStats {
  return {
    status: dto.status,
    summary: { ...dto.summary },
    evolution: dto.evolution.map((p) => ({ ...p })),
    byWorkshop: dto.byWorkshop.map((w) => ({ ...w })),
    byDistance: dto.byDistance.map((d) => ({ ...d })),
    byResult: dto.byResult.map((r) => ({
      result: r.result as ShootingStats['byResult'][number]['result'],
      count: r.count,
    })),
    heatmap: dto.heatmap.map((c) => ({ ...c })),
  }
}

function mapJumpShotStats(dto: JumpShotStatsResponseDto): JumpShotStats {
  return {
    status: dto.status,
    summary: { ...dto.summary },
    evolution: dto.evolution.map((p) => ({ ...p })),
    byDistance: dto.byDistance.map((d) => ({
      distance: d.distance as JumpShotDistance,
      throwCount: d.throwCount,
      successRate: d.successRate,
    })),
    byResult: dto.byResult.map((r) => ({
      result: r.result as JumpShotThrowResult,
      count: r.count,
    })),
  }
}

function mapCoachList(dto: CoachPlayerListResponseDto): CoachPlayerList {
  return {
    clubId: dto.clubId,
    clubName: dto.clubName,
    selectionGlobaleId: dto.selectionGlobaleId,
    selectionGlobaleName: dto.selectionGlobaleName,
    from: dto.from,
    to: dto.to,
    items: dto.items.map((item) => ({
      id: item.id,
      firstName: item.firstName,
      lastName: item.lastName,
      nickname: item.nickname,
      point: { ...item.point },
      tir: { ...item.tir },
      shooting: { ...item.shooting },
      pointing: { ...item.pointing },
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

export interface CoachSpaceQuery {
  selectionGlobaleId?: number | null
}

function coachSpaceParams(space?: CoachSpaceQuery): Record<string, number> {
  if (space?.selectionGlobaleId) {
    return { selectionGlobaleId: space.selectionGlobaleId }
  }
  return {}
}

export const coachService = {
  async listPlayers(
    range: StatsDateRangeParams,
    nature?: MatchNature | 'all',
    space?: CoachSpaceQuery,
  ): Promise<CoachPlayerList> {
    const { data } = await api.get<CoachPlayerListResponseDto>('/coach/players', {
      params: {
        ...range,
        ...coachSpaceParams(space),
        ...(nature && nature !== 'all' ? { nature } : {}),
      },
    })
    return mapCoachList(data)
  },

  async createPlayer(payload: CreateCoachPlayerRequest, space?: CoachSpaceQuery): Promise<Player> {
    const { data } = await api.post<CreatePlayerResponseDto>('/coach/players', payload, {
      params: coachSpaceParams(space),
    })
    return {
      id: data.id,
      firstName: data.firstName,
      lastName: data.lastName,
      nickname: data.nickname,
      clubId: data.clubId,
      clubName: data.clubName,
    }
  },

  async searchAvailablePlayers(query: string, space?: CoachSpaceQuery): Promise<Player[]> {
    const { data } = await api.get<PlayerItemDto[]>('/coach/players/available', {
      params: { q: query, ...coachSpaceParams(space) },
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

  async attachPlayer(playerId: number, space?: CoachSpaceQuery): Promise<Player> {
    const { data } = await api.post<PlayerItemDto>(`/coach/players/${playerId}/attach`, null, {
      params: coachSpaceParams(space),
    })
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
    space?: CoachSpaceQuery,
  ): Promise<PlayerStats> {
    const { data } = await api.get<PlayerStatsResponseDto>(`/coach/players/${playerId}/stats`, {
      params: {
        ...range,
        ...coachSpaceParams(space),
        ...(nature && nature !== 'all' ? { nature } : {}),
        ...(type && type !== 'all' ? { type } : {}),
        ...(distance && distance !== 'all' ? { distance } : {}),
        ...(competitionId && competitionId !== 'all' ? { competitionId } : {}),
      },
    })
    return mapPlayerStats(data)
  },

  async getPlayerTacticalInsights(
    playerId: number,
    range: StatsDateRangeParams,
    nature?: MatchNature | 'all',
    type?: MatchType | 'all',
    distance?: DistanceBucketKey | 'all',
    competitionId?: number | 'all',
    space?: CoachSpaceQuery,
  ): Promise<PlayerTacticalInsights> {
    const { data } = await api.get<PlayerTacticalInsightsResponseDto>(
      `/coach/players/${playerId}/stats/tactical-insights`,
      {
        params: {
          ...range,
          ...coachSpaceParams(space),
          ...(nature && nature !== 'all' ? { nature } : {}),
          ...(type && type !== 'all' ? { type } : {}),
          ...(distance && distance !== 'all' ? { distance } : {}),
          ...(competitionId && competitionId !== 'all' ? { competitionId } : {}),
        },
      },
    )
    return data
  },

  async getPlayerShootingStats(
    playerId: number,
    range: StatsDateRangeParams,
    nature?: ShootingContextNature | 'all',
    space?: CoachSpaceQuery,
  ): Promise<ShootingStats> {
    const { data } = await api.get<ShootingStatsResponseDto>(`/coach/players/${playerId}/shooting/stats`, {
      params: {
        ...range,
        ...coachSpaceParams(space),
        ...(nature && nature !== 'all' ? { nature } : {}),
      },
    })
    return mapShootingStats(data)
  },

  async getPlayerPointingStats(
    playerId: number,
    range: StatsDateRangeParams,
    nature?: PointingContextNature | 'all',
    space?: CoachSpaceQuery,
  ): Promise<PointingStats> {
    const { data } = await api.get<PointingStatsResponseDto>(`/coach/players/${playerId}/pointing/stats`, {
      params: {
        ...range,
        ...coachSpaceParams(space),
        ...(nature && nature !== 'all' ? { nature } : {}),
      },
    })
    return mapPointingStats(data)
  },

  async getPlayerJumpShotStats(
    playerId: number,
    range: StatsDateRangeParams,
    space?: CoachSpaceQuery,
  ): Promise<JumpShotStats> {
    const { data } = await api.get<JumpShotStatsResponseDto>(`/coach/players/${playerId}/jump-shot/stats`, {
      params: {
        ...range,
        ...coachSpaceParams(space),
      },
    })
    return mapJumpShotStats(data)
  },

  async getPlayerHistory(
    playerId: number,
    page = 1,
    pageSize = 20,
    space?: CoachSpaceQuery,
  ): Promise<MatchHistoryPage> {
    const { data } = await api.get<MatchHistoryResponseDto>(`/coach/players/${playerId}/matches/history`, {
      params: { page, pageSize, ...coachSpaceParams(space) },
    })
    return mapHistoryPage(data)
  },
}
