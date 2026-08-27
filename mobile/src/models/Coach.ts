export interface CoachPlayerShotSummary {
  average: number | null
  successCount: number | null
  totalCount: number | null
}

export interface CoachPlayerListItem {
  id: number
  firstName: string
  lastName: string
  nickname: string
  point: CoachPlayerShotSummary
  tir: CoachPlayerShotSummary
}

export interface CoachPlayerList {
  clubId: number
  clubName: string
  from: string
  to: string
  items: CoachPlayerListItem[]
}
