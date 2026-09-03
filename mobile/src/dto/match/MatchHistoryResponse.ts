import type { CompetitionStage, MatchNature } from '../../models/MatchContext'

export interface MatchHistoryItemDto {
  id: number
  date: string // ISO datetime string
  type: 'tete_a_tete' | 'doublette' | 'triplette'
  scoreA: number
  scoreB: number
  winner: 'A' | 'B'
  victory: boolean | null
  nature: MatchNature | null
  competitionLabel: string | null
  competitionStage: CompetitionStage | null
  refused?: boolean
}

export interface MatchHistoryResponseDto {
  page: number
  pageSize: number
  total: number
  items: MatchHistoryItemDto[]
}
