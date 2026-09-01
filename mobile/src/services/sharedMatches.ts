import axios from 'axios'
import { getApiBaseUrl } from '../utils/apiBaseUrl'
import type { MatchSummaryResponseDto } from '../dto/match/MatchSummaryResponse'
import type { MatchContextResponseDto } from '../dto/match/MatchContextResponse'
import type { MatchSummary } from '../models/MatchSummary'
import type { MatchContext } from '../models/MatchContext'
import { todayInputDate } from '../models/MatchContext'

export interface SharedMatchRecapDto {
  summary: MatchSummaryResponseDto & {
    shareUuid?: string | null
    myMatchPlayerId?: number | null
    myHasValidatedMatch?: boolean | null
  }
  context: MatchContextResponseDto
  competitionLabel?: string | null
}

export interface SharedMatchRecap {
  summary: MatchSummary
  context: MatchContext
  competitionLabel: string | null
}

const publicApi = axios.create({
  baseURL: getApiBaseUrl(),
})

function mapContext(dto: MatchContextResponseDto): MatchContext {
  return {
    matchId: dto.matchId,
    comment: dto.comment,
    teamAName: dto.teamAName,
    teamBName: dto.teamBName,
    nature: dto.nature,
    competitionId: dto.competitionId,
    competitionName: dto.competitionName,
    competitionStage: dto.competitionStage,
    terrainType: dto.terrainType,
    playedAt: dto.playedAt ?? todayInputDate(),
  }
}

export const sharedMatchesService = {
  async getPublic(uuid: string): Promise<SharedMatchRecap> {
    const { data } = await publicApi.get<SharedMatchRecapDto>(`/shared-matches/${uuid}`)
    return {
      summary: data.summary as unknown as MatchSummary,
      context: mapContext(data.context),
      competitionLabel: data.competitionLabel ?? null,
    }
  },
}
