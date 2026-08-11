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
  title: string | null
  description: string | null
  workshops: ShootingWorkshopSummaryDto[]
}

export interface ShootingSessionHistoryItemDto {
  id: number
  createdAt: string
  finishedAt: string
  totalScore: number
  title: string | null
}

export interface UpdateShootingSessionContextRequestDto {
  title: string | null
  description: string | null
}

export interface ShootingSessionHistoryResponseDto {
  page: number
  pageSize: number
  total: number
  items: ShootingSessionHistoryItemDto[]
}
