export type MatchNature = 'friendly' | 'training' | 'competition'

export type CompetitionStage =
  | 'group'
  | 'swiss'
  | 'top_64'
  | 'top_32'
  | 'top_16'
  | 'quarter_final'
  | 'semi_final'
  | 'final'
  | 'other'

/** TODO: business validation pending — see docs/02-ux.md */
export type TerrainType = 'gravel' | 'stabilized' | 'indoor' | 'other'

export interface MatchContext {
  matchId: number
  comment: string | null
  teamAName: string | null
  teamBName: string | null
  nature: MatchNature | null
  competitionName: string | null
  competitionStage: CompetitionStage | null
  terrainType: TerrainType | null
}

export interface MatchContextForm {
  comment: string
  teamAName: string
  teamBName: string
  nature: MatchNature | null
  competitionName: string
  competitionStage: CompetitionStage | null
  terrainType: TerrainType | null
}

export function emptyMatchContextForm(): MatchContextForm {
  return {
    comment: '',
    teamAName: '',
    teamBName: '',
    nature: null,
    competitionName: '',
    competitionStage: null,
    terrainType: null,
  }
}

export function matchContextToForm(context: MatchContext): MatchContextForm {
  return {
    comment: context.comment ?? '',
    teamAName: context.teamAName ?? '',
    teamBName: context.teamBName ?? '',
    nature: context.nature,
    competitionName: context.competitionName ?? '',
    competitionStage: context.competitionStage,
    terrainType: context.terrainType,
  }
}

export function hasMatchContextData(context: MatchContext): boolean {
  return (
    (context.comment !== null && context.comment !== '') ||
    (context.teamAName !== null && context.teamAName !== '') ||
    (context.teamBName !== null && context.teamBName !== '') ||
    context.nature !== null ||
    (context.competitionName !== null && context.competitionName !== '') ||
    context.competitionStage !== null ||
    context.terrainType !== null
  )
}
