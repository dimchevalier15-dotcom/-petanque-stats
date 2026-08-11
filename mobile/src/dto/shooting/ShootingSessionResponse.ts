import type { ShootingShotResult } from '../../models/Shooting'

export interface ShootingSessionStartedResponseDto {
  id: number
  createdAt: string
}

export interface ShootingShotSummaryDto {
  distance: number
  result: ShootingShotResult
  score: number
}

export interface ShootingWorkshopSummaryDto {
  workshop: number
  totalScore: number
  shots: ShootingShotSummaryDto[]
}

export interface ShootingSessionSummaryResponseDto {
  id: number
  createdAt: string
  finishedAt: string | null
  totalScore: number | null
  workshops: ShootingWorkshopSummaryDto[]
}

export interface ShootingSessionHistoryItemDto {
  id: number
  createdAt: string
  finishedAt: string
  totalScore: number
}

export interface ShootingSessionHistoryResponseDto {
  page: number
  pageSize: number
  total: number
  items: ShootingSessionHistoryItemDto[]
}
