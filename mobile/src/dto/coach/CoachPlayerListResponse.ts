export interface CoachPlayerShotSummaryDto {
  average: number | null
  successCount: number | null
  totalCount: number | null
}

export interface CoachPlayerListItemDto {
  id: number
  firstName: string
  lastName: string
  nickname: string
  point: CoachPlayerShotSummaryDto
  tir: CoachPlayerShotSummaryDto
}

export interface CoachPlayerListResponseDto {
  clubId: number
  clubName: string
  from: string
  to: string
  items: CoachPlayerListItemDto[]
}
