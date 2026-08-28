import type { ShootingContextNature, ShootingShotResult } from '../../models/Shooting'

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
  playedAt: string
  finishedAt: string | null
  totalScore: number | null
  contextNature: ShootingContextNature | null
  title: string | null
  description: string | null
  workshops: ShootingWorkshopSummaryDto[]
}

export interface ShootingSessionHistoryItemDto {
  id: number
  createdAt: string
  playedAt: string
  finishedAt: string
  totalScore: number
  contextNature: ShootingContextNature | null
  title: string | null
}

export interface UpdateShootingSessionContextRequestDto {
  contextNature: ShootingContextNature | null
  playedAt?: string | null
  title: string | null
  description: string | null
}

export interface ShootingSessionHistoryResponseDto {
  page: number
  pageSize: number
  total: number
  items: ShootingSessionHistoryItemDto[]
}
