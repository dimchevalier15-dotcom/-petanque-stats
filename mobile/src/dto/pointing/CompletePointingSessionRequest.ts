import type { PointingShotResult } from '../../models/Pointing'

export interface PointingShotInputDto {
  workshop: number
  distance: number
  result: PointingShotResult
}

export interface CompletePointingSessionRequestDto {
  shots: PointingShotInputDto[]
}
