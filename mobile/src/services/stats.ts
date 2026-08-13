import api from './http'
import type { MatchNature } from '../models/MatchContext'
import type { PlayerStatsResponseDto } from '../dto/player/PlayerStatsResponse'
import type { StatsDateRangeParams } from '../composables/useStatsDateRange'
import type { PlayerStats } from '../models/PlayerStats'

function mapPlayerStats(dto: PlayerStatsResponseDto): PlayerStats {
  return {
    status: dto.status,
    playerId: dto.playerId,
    displayName: dto.displayName,
    summary: { ...dto.summary },
    overall: dto.overall,
    point: dto.point,
    tir: dto.tir,
    evolution: dto.evolution.map((p) => ({ ...p })),
    byNature: dto.byNature.map((n) => ({
      ...n,
      nature: n.nature as PlayerStats['byNature'][number]['nature'],
    })),
  }
}

export const statsService = {
  async getMyStats(range: StatsDateRangeParams, nature?: MatchNature | 'all'): Promise<PlayerStats> {
    const { data } = await api.get<PlayerStatsResponseDto>('/players/me/stats', {
      params: {
        ...range,
        ...(nature && nature !== 'all' ? { nature } : {}),
      },
    })
    return mapPlayerStats(data)
  },
}
