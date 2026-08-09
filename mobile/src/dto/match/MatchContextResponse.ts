import type { CompetitionStage, MatchNature, TerrainType } from '../../models/MatchContext'

export interface MatchContextResponseDto {
  matchId: number
  comment: string | null
  teamAName: string | null
  teamBName: string | null
  nature: MatchNature | null
  competitionName: string | null
  competitionStage: CompetitionStage | null
  terrainType: TerrainType | null
}
