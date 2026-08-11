import type { ShootingShotResult } from '../../models/Shooting'

export interface ShootingShotInputDto {
  workshop: number
  distance: number
  result: ShootingShotResult
}

export interface CompleteShootingSessionRequestDto {
  shots: ShootingShotInputDto[]
}
