export interface MatchHistoryItem {
  id: number
  date: string // ISO datetime string
  type: 'tete_a_tete' | 'doublette' | 'triplette'
  scoreA: number
  scoreB: number
  winner: 'A' | 'B'
  victory: boolean
}

export interface MatchHistoryPage {
  page: number
  pageSize: number
  total: number
  items: MatchHistoryItem[]
}
