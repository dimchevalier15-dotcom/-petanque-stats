import type { PointingShotResult } from '../../models/Pointing'

export interface PointingSessionStartedResponseDto {
  id: number
  createdAt: string
}

export interface PointingShotSummaryDto {
  distance: number
  result: PointingShotResult
  score: number
}

export interface PointingWorkshopSummaryDto {
  workshop: number
  totalScore: number
  shots: PointingShotSummaryDto[]
}

export interface PointingSessionSummaryResponseDto {
  id: number
  createdAt: string
  playedAt: string
  finishedAt: string | null
  totalScore: number | null
  contextNature: string | null
  title: string | null
  description: string | null
  workshops: PointingWorkshopSummaryDto[]
}
