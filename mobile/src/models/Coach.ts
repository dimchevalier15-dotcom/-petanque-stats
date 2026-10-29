export interface CoachPlayerShotSummary {
  average: number | null
  successCount: number | null
  totalCount: number | null
}

export interface CoachPlayerShootingSummary {
  sessionsCount: number
  averageSessionScore: number | null
  bestSessionScore: number | null
}

export interface CoachPlayerPointingSummary {
  sessionsCount: number
  averageSessionScore: number | null
  bestSessionScore: number | null
}

export interface CoachPlayerListItem {
  id: number
  firstName: string
  lastName: string
  nickname: string
  point: CoachPlayerShotSummary
  tir: CoachPlayerShotSummary
  shooting: CoachPlayerShootingSummary
  pointing: CoachPlayerPointingSummary
}

export interface CoachPlayerList {
  clubId: number | null
  clubName: string | null
  selectionGlobaleId: number | null
  selectionGlobaleName: string | null
  from: string | null
  to: string | null
  items: CoachPlayerListItem[]
}
