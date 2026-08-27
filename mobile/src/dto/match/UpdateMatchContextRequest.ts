import type { CompetitionStage, MatchNature, TerrainType } from '../../models/MatchContext'

export interface UpdateMatchContextRequestDto {
  comment?: string | null
  teamAName?: string | null
  teamBName?: string | null
  nature?: MatchNature | null
  competitionId?: number | null
  competitionName?: string | null
  competitionStage?: CompetitionStage | null
  terrainType?: TerrainType | null
  playedAt?: string | null
}
