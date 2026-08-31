export interface PendingValidationItem {
  matchPlayerId: number
  matchId: number
  date: string
  type: 'tete_a_tete' | 'doublette' | 'triplette'
  scoreA: number
  scoreB: number
  teamALabel: string
  teamBLabel: string
  nature?: string | null
  competitionLabel?: string | null
  competitionStage?: string | null
}

export interface PendingValidationPage {
  total: number
  items: PendingValidationItem[]
}
