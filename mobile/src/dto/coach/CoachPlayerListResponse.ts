export interface CoachPlayerShotSummaryDto {
  average: number | null
  successCount: number | null
  totalCount: number | null
}

export interface CoachPlayerShootingSummaryDto {
  sessionsCount: number
  averageSessionScore: number | null
  bestSessionScore: number | null
}

export interface CoachPlayerPointingSummaryDto {
  sessionsCount: number
  averageSessionScore: number | null
  bestSessionScore: number | null
}

export interface CoachPlayerListItemDto {
  id: number
  firstName: string
  lastName: string
  nickname: string
  point: CoachPlayerShotSummaryDto
  tir: CoachPlayerShotSummaryDto
  shooting: CoachPlayerShootingSummaryDto
  pointing: CoachPlayerPointingSummaryDto
}

export interface CoachPlayerListResponseDto {
  clubId: number | null
  clubName: string | null
  selectionGlobaleId: number | null
  selectionGlobaleName: string | null
  from: string | null
  to: string | null
  items: CoachPlayerListItemDto[]
}
