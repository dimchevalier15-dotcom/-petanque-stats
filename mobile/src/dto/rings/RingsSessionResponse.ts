import type { RingsMode, RingsTarget } from '../../models/Rings'

export interface RingsSessionStartedResponseDto {
  id: number
  mode: RingsMode
  createdAt: string
}

export interface RingsThrowSummaryDto {
  position: number
  target: RingsTarget
  success: boolean
  score: number
}

export interface RingsSessionSummaryResponseDto {
  id: number
  mode: RingsMode
  createdAt: string
  playedAt: string
  finishedAt: string | null
  totalScore: number | null
  throws: RingsThrowSummaryDto[]
}

export interface RingsSessionHistoryItemDto {
  id: number
  mode: RingsMode
  playedAt: string
  finishedAt: string
  totalScore: number
}

export interface RingsSessionHistoryResponseDto {
  page: number
  pageSize: number
  total: number
  items: RingsSessionHistoryItemDto[]
}
