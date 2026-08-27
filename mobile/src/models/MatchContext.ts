import { COMPETITION_OTHER_VALUE, type CompetitionSelection } from './Competition'

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
  competitionId: number | null
  competitionName: string | null
  competitionStage: CompetitionStage | null
  terrainType: TerrainType | null
  playedAt: string
}

export interface MatchContextForm {
  comment: string
  teamAName: string
  teamBName: string
  nature: MatchNature | null
  competitionSelection: CompetitionSelection
  competitionName: string
  competitionStage: CompetitionStage | null
  terrainType: TerrainType | null
  playedAt: string
}

export function emptyMatchContextForm(): MatchContextForm {
  return {
    comment: '',
    teamAName: '',
    teamBName: '',
    nature: null,
    competitionSelection: null,
    competitionName: '',
    competitionStage: null,
    terrainType: null,
    playedAt: todayInputDate(),
  }
}

export function todayInputDate(date = new Date()): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

export function playedAtToInputDate(playedAt: string): string {
  return playedAt.slice(0, 10)
}

export function formatPlayedAt(playedAt: string, locale?: string): string {
  const [year, month, day] = playedAtToInputDate(playedAt).split('-').map(Number)
  if (!year || !month || !day) {
    return playedAt
  }
  return new Date(year, month - 1, day).toLocaleDateString(locale)
}

export function matchContextToForm(context: MatchContext): MatchContextForm {
  let competitionSelection: CompetitionSelection = null
  if (context.competitionId !== null) {
    competitionSelection = context.competitionId
  } else if (context.competitionName) {
    competitionSelection = COMPETITION_OTHER_VALUE
  }

  return {
    comment: context.comment ?? '',
    teamAName: context.teamAName ?? '',
    teamBName: context.teamBName ?? '',
    nature: context.nature,
    competitionSelection,
    competitionName: context.competitionName ?? '',
    competitionStage: context.competitionStage,
    terrainType: context.terrainType,
    playedAt: playedAtToInputDate(context.playedAt),
  }
}

export function hasMatchContextData(context: MatchContext): boolean {
  return (
    (context.comment !== null && context.comment !== '') ||
    (context.teamAName !== null && context.teamAName !== '') ||
    (context.teamBName !== null && context.teamBName !== '') ||
    context.nature !== null ||
    context.competitionId !== null ||
    (context.competitionName !== null && context.competitionName !== '') ||
    context.competitionStage !== null ||
    context.terrainType !== null
  )
}
